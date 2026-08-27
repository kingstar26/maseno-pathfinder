<?php

declare(strict_types=1);

/** @return array<string, mixed> */
function graduate_surveys_profile(mysqli $connection, int $userId): array
{
    $statement = $connection->prepare('SELECT graduate_profile_id FROM graduate_profiles WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $profile = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($profile)) {
        json_error('Graduate profile was not found.', [], 404);
    }
    return $profile;
}

function get_or_create_response_status(mysqli $connection, string $code, string $name, int $isFinal): int
{
    $statement = $connection->prepare('SELECT survey_response_status_id FROM survey_response_statuses WHERE code = ? LIMIT 1');
    $statement->bind_param('s', $code);
    $statement->execute();
    $result = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (is_array($result)) {
        return (int)$result['survey_response_status_id'];
    }

    // Create the status if it doesn't exist
    $statement = $connection->prepare('INSERT INTO survey_response_statuses (code, name, is_final) VALUES (?, ?, ?)');
    $statement->bind_param('ssi', $code, $name, $isFinal);
    $statement->execute();
    $id = (int)$connection->insert_id;
    $statement->close();

    return $id;
}

function list_available_surveys(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $profile = graduate_surveys_profile($connection, $authenticated['user_id']);
    $graduateProfileId = (int) $profile['graduate_profile_id'];

    $statement = $connection->prepare(
        'SELECT s.survey_id,
                s.name AS title,
                s.name,
                NULL AS description,
                s.is_active,
                s.available_from,
                s.available_until,
                s.created_at,
                s.updated_at,
                COALESCE(status.name, \'Pending\') AS invitation_status,
                COALESCE(status.code, \'PENDING\') AS invitation_status_code,
                CASE WHEN status.is_final = 1 OR UPPER(status.code) = \'SUBMITTED\' THEN \'completed\' ELSE \'pending\' END AS completion_status
         FROM surveys AS s
         LEFT JOIN survey_invitations AS i ON i.survey_id = s.survey_id AND i.graduate_profile_id = ?
         LEFT JOIN survey_response_statuses AS status ON status.survey_response_status_id = i.survey_response_status_id
         WHERE s.is_active = 1
           AND (s.available_from IS NULL OR s.available_from <= NOW(6))
           AND (s.available_until IS NULL OR s.available_until >= NOW(6))
         ORDER BY s.available_from DESC, s.survey_id DESC'
    );
    $statement->bind_param('i', $graduateProfileId);
    $statement->execute();
    $surveys = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    foreach ($surveys as &$survey) {
        $survey['survey_id'] = (int) $survey['survey_id'];
        $survey['is_active'] = (int) $survey['is_active'];
        $survey['status'] = $survey['is_active'] === 1 ? 'active' : 'inactive';
    }
    unset($survey);

    json_success('Available surveys retrieved successfully.', $surveys);
}

function get_survey_details(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $profile = graduate_surveys_profile($connection, $authenticated['user_id']);
    $graduateProfileId = (int) $profile['graduate_profile_id'];
    
    // Fetch survey details
    $statement = $connection->prepare(
        'SELECT s.survey_id, s.name AS title, s.name, NULL AS description, s.is_active, s.available_from, s.available_until,
                COALESCE(status.name, \'Pending\') AS invitation_status,
                CASE WHEN status.is_final = 1 OR UPPER(status.code) = \'SUBMITTED\' THEN 1 ELSE 0 END AS is_completed
         FROM surveys AS s
         LEFT JOIN survey_invitations AS i ON i.survey_id = s.survey_id AND i.graduate_profile_id = ?
         LEFT JOIN survey_response_statuses AS status ON status.survey_response_status_id = i.survey_response_status_id
         WHERE s.survey_id = ? AND s.is_active = 1 
         LIMIT 1'
    );
    $statement->bind_param('ii', $graduateProfileId, $surveyId);
    $statement->execute();
    $survey = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($survey)) {
        json_error('Survey was not found or is inactive.', [], 404);
    }

    $survey['survey_id'] = (int) $survey['survey_id'];
    $survey['is_active'] = (int) $survey['is_active'];
    $survey['is_completed'] = ((int)$survey['is_completed']) === 1;

    // Fetch survey sections
    $statement = $connection->prepare(
        'SELECT survey_section_id, title, display_order, is_required 
         FROM survey_sections 
         WHERE survey_id = ? 
         ORDER BY display_order ASC, survey_section_id ASC'
    );
    $statement->bind_param('i', $surveyId);
    $statement->execute();
    $sections = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    // Fetch survey questions
    $statement = $connection->prepare(
        'SELECT q.survey_question_id, q.survey_section_id, q.prompt, q.response_type, q.is_required, q.display_order
         FROM survey_questions AS q
         INNER JOIN survey_sections AS sec ON sec.survey_section_id = q.survey_section_id
         WHERE sec.survey_id = ?
         ORDER BY sec.display_order ASC, q.display_order ASC, q.survey_question_id ASC'
    );
    $statement->bind_param('i', $surveyId);
    $statement->execute();
    $questions = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    // Fetch survey options
    $statement = $connection->prepare(
        'SELECT o.survey_question_option_id, o.survey_question_id, o.label, o.value, o.display_order
         FROM survey_question_options AS o
         INNER JOIN survey_questions AS q ON q.survey_question_id = o.survey_question_id
         INNER JOIN survey_sections AS sec ON sec.survey_section_id = q.survey_section_id
         WHERE sec.survey_id = ?
         ORDER BY o.display_order ASC, o.survey_question_option_id ASC'
    );
    $statement->bind_param('i', $surveyId);
    $statement->execute();
    $options = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    // Group options by question
    $optionsByQuestion = [];
    foreach ($options as $option) {
        $optionId = (int) $option['survey_question_option_id'];
        $questionId = (int) $option['survey_question_id'];
        $optionsByQuestion[$questionId][] = [
            'option_id' => $optionId,
            'label' => $option['label'],
            'value' => $option['value'],
            'display_order' => (int) $option['display_order']
        ];
    }

    // Group questions by section
    $questionsBySection = [];
    foreach ($questions as $question) {
        $questionId = (int) $question['survey_question_id'];
        $sectionId = (int) $question['survey_section_id'];
        $questionsBySection[$sectionId][] = [
            'question_id' => $questionId,
            'prompt' => $question['prompt'],
            'response_type' => $question['response_type'],
            'is_required' => (int) $question['is_required'],
            'display_order' => (int) $question['display_order'],
            'options' => $optionsByQuestion[$questionId] ?? []
        ];
    }

    // Format section details and embed questions
    $formattedSections = [];
    foreach ($sections as $section) {
        $sectionId = (int) $section['survey_section_id'];
        $formattedSections[] = [
            'section_id' => $sectionId,
            'title' => $section['title'],
            'display_order' => (int) $section['display_order'],
            'is_required' => (int) $section['is_required'],
            'questions' => $questionsBySection[$sectionId] ?? []
        ];
    }

    $survey['sections'] = $formattedSections;

    json_success('Survey details retrieved successfully.', $survey);
}

function start_survey_response(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();

    // Check survey exists, is active, and is currently available
    $statement = $connection->prepare(
        'SELECT survey_id, is_active, available_from, available_until 
         FROM surveys 
         WHERE survey_id = ? 
         LIMIT 1'
    );
    $statement->bind_param('i', $surveyId);
    $statement->execute();
    $survey = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($survey) || (int)$survey['is_active'] !== 1) {
        json_error('Survey was not found or is inactive.', [], 404);
    }

    $now = new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi'));
    if ($survey['available_from'] !== null) {
        $from = new DateTimeImmutable((string)$survey['available_from']);
        if ($now < $from) {
            json_error('Validation failed.', ['survey_id' => 'This survey is not yet available.'], 422);
        }
    }
    if ($survey['available_until'] !== null) {
        $until = new DateTimeImmutable((string)$survey['available_until']);
        if ($now > $until) {
            json_error('Validation failed.', ['survey_id' => 'This survey has expired.'], 422);
        }
    }

    try {
        $connection->begin_transaction();

        $profile = graduate_surveys_profile($connection, $authenticated['user_id']);
        $graduateProfileId = (int) $profile['graduate_profile_id'];

        $inProgressStatusId = get_or_create_response_status($connection, 'in_progress', 'In Progress', 0);

        $statement = $connection->prepare(
            'SELECT i.survey_invitation_id, i.survey_response_status_id, s.is_final 
             FROM survey_invitations AS i
             INNER JOIN survey_response_statuses AS s ON s.survey_response_status_id = i.survey_response_status_id
             WHERE i.survey_id = ? AND i.graduate_profile_id = ? 
             LIMIT 1'
        );
        $statement->bind_param('ii', $surveyId, $graduateProfileId);
        $statement->execute();
        $invitation = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (is_array($invitation)) {
            $invitationId = (int) $invitation['survey_invitation_id'];
            $isFinal = (int) $invitation['is_final'];

            if ($isFinal === 1) {
                json_error('Validation failed.', ['survey_id' => 'You have already submitted a response for this survey.'], 422);
            }

            $statement = $connection->prepare(
                'SELECT survey_response_id, survey_invitation_id, submitted_at, created_at, updated_at 
                 FROM survey_responses 
                 WHERE survey_invitation_id = ? AND submitted_at IS NULL 
                 LIMIT 1'
             );
             $statement->bind_param('i', $invitationId);
             $statement->execute();
             $existingResponse = $statement->get_result()->fetch_assoc();
             $statement->close();

             if (is_array($existingResponse)) {
                 $connection->commit();
                 $existingResponse['survey_response_id'] = (int) $existingResponse['survey_response_id'];
                 $existingResponse['survey_invitation_id'] = (int) $existingResponse['survey_invitation_id'];
                 json_success('Existing survey response loaded.', $existingResponse);
             }

             $statement = $connection->prepare(
                 'INSERT INTO survey_responses (survey_invitation_id) VALUES (?)'
             );
             $statement->bind_param('i', $invitationId);
             $statement->execute();
             $responseId = (int)$connection->insert_id;
             $statement->close();
        } else {
             $statement = $connection->prepare(
                 'INSERT INTO survey_invitations (survey_id, graduate_profile_id, survey_response_status_id, sent_at) 
                  VALUES (?, ?, ?, NOW(6))'
             );
             $statement->bind_param('iii', $surveyId, $graduateProfileId, $inProgressStatusId);
             $statement->execute();
             $invitationId = (int)$connection->insert_id;
             $statement->close();

             $statement = $connection->prepare(
                 'INSERT INTO survey_responses (survey_invitation_id) VALUES (?)'
             );
             $statement->bind_param('i', $invitationId);
             $statement->execute();
             $responseId = (int)$connection->insert_id;
             $statement->close();
        }

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $statement = $connection->prepare(
        'SELECT survey_response_id, survey_invitation_id, submitted_at, created_at, updated_at 
         FROM survey_responses 
         WHERE survey_response_id = ? 
         LIMIT 1'
    );
    $statement->bind_param('i', $responseId);
    $statement->execute();
    $response = $statement->get_result()->fetch_assoc();
    $statement->close();

    if ($response !== null) {
        $response['survey_response_id'] = (int) $response['survey_response_id'];
        $response['survey_invitation_id'] = (int) $response['survey_invitation_id'];
    }

    json_success('Survey response started successfully.', $response, 201);
}

function submit_survey_response(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $input = request_json_body();
    $answers = $input['answers'] ?? null;
    if (!is_array($answers)) {
        json_error('Validation failed.', ['answers' => 'Provide a valid array of answers.'], 422);
    }

    $connection = database_connection();
    $profile = graduate_surveys_profile($connection, $authenticated['user_id']);
    $graduateProfileId = (int) $profile['graduate_profile_id'];

    // 1. Fetch survey details
    $statement = $connection->prepare(
        'SELECT survey_id, is_active 
         FROM surveys 
         WHERE survey_id = ? AND is_active = 1 
         LIMIT 1'
    );
    $statement->bind_param('i', $surveyId);
    $statement->execute();
    $survey = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($survey)) {
        json_error('Survey was not found or is inactive.', [], 404);
    }

    // 2. Fetch invitation for this graduate and survey
    $statement = $connection->prepare(
        'SELECT i.survey_invitation_id, i.survey_response_status_id, status.code AS status_code, status.is_final 
         FROM survey_invitations AS i
         LEFT JOIN survey_response_statuses AS status ON status.survey_response_status_id = i.survey_response_status_id
         WHERE i.survey_id = ? AND i.graduate_profile_id = ? 
         LIMIT 1'
    );
    $statement->bind_param('ii', $surveyId, $graduateProfileId);
    $statement->execute();
    $invitation = $statement->get_result()->fetch_assoc();
    $statement->close();

    // If no invitation exists, create pending invitation for this assigned graduate
    if (!is_array($invitation)) {
        $pendingStatusId = get_or_create_response_status($connection, 'PENDING', 'Pending', 0);
        $statement = $connection->prepare(
            'INSERT INTO survey_invitations (survey_id, graduate_profile_id, survey_response_status_id, sent_at) 
             VALUES (?, ?, ?, NOW(6))'
        );
        $statement->bind_param('iii', $surveyId, $graduateProfileId, $pendingStatusId);
        $statement->execute();
        $invitationId = (int)$connection->insert_id;
        $statement->close();

        $invitation = [
            'survey_invitation_id' => $invitationId,
            'survey_response_status_id' => $pendingStatusId,
            'status_code' => 'PENDING',
            'is_final' => 0,
        ];
    }

    $invitationId = (int) $invitation['survey_invitation_id'];
    $isFinal = (int) ($invitation['is_final'] ?? 0);
    $statusCode = strtoupper((string)($invitation['status_code'] ?? ''));

    if ($isFinal === 1 || $statusCode === 'SUBMITTED') {
        json_error('Validation failed.', ['survey_id' => 'You have already submitted a response for this survey.'], 422);
    }

    // 3. Find or check existing response row
    $statement = $connection->prepare(
        'SELECT survey_response_id, submitted_at 
         FROM survey_responses 
         WHERE survey_invitation_id = ? 
         LIMIT 1'
    );
    $statement->bind_param('i', $invitationId);
    $statement->execute();
    $existingResponse = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (is_array($existingResponse) && $existingResponse['submitted_at'] !== null) {
        json_error('Validation failed.', ['survey_id' => 'You have already submitted a response for this survey.'], 422);
    }

    // 4. Validate questions and options
    $statement = $connection->prepare(
        'SELECT q.survey_question_id, q.prompt, q.is_required, q.response_type 
         FROM survey_questions AS q
         INNER JOIN survey_sections AS sec ON sec.survey_section_id = q.survey_section_id
         WHERE sec.survey_id = ?'
    );
    $statement->bind_param('i', $surveyId);
    $statement->execute();
    $questionsList = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    $questions = [];
    foreach ($questionsList as $q) {
        $questions[(int)$q['survey_question_id']] = $q;
    }

    $statement = $connection->prepare(
        'SELECT o.survey_question_option_id, o.survey_question_id 
         FROM survey_question_options AS o
         INNER JOIN survey_questions AS q ON q.survey_question_id = o.survey_question_id
         INNER JOIN survey_sections AS sec ON sec.survey_section_id = q.survey_section_id
         WHERE sec.survey_id = ?'
    );
    $statement->bind_param('i', $surveyId);
    $statement->execute();
    $optionsList = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    $validOptions = [];
    foreach ($optionsList as $opt) {
        $validOptions[(int)$opt['survey_question_option_id']] = (int)$opt['survey_question_id'];
    }

    $submittedAnswersIndex = [];
    $validationErrors = [];

    foreach ($answers as $index => $answer) {
        if (!is_array($answer)) {
            json_error('Validation failed.', ['answers' => 'Format of answers array is invalid.'], 422);
        }

        if (!isset($answer['question_id'])) {
            json_error('Validation failed.', ["answers.{$index}.question_id" => 'Question ID is required.'], 422);
        }

        $questionId = (int)$answer['question_id'];
        if (!isset($questions[$questionId])) {
            json_error('Validation failed.', ["answers.{$index}.question_id" => 'Question does not belong to this survey.'], 422);
        }

        $optionId = isset($answer['option_id']) && $answer['option_id'] !== null ? (int)$answer['option_id'] : null;
        $answerText = isset($answer['answer_text']) && $answer['answer_text'] !== null ? trim((string)$answer['answer_text']) : null;

        if ($optionId !== null) {
            if (!isset($validOptions[$optionId]) || $validOptions[$optionId] !== $questionId) {
                json_error('Validation failed.', ["answers.{$index}.option_id" => 'Select a valid option for this question.'], 422);
            }
        }

        $submittedAnswersIndex[$questionId] = [
            'option_id' => $optionId,
            'answer_text' => $answerText !== '' ? $answerText : null,
        ];
    }

    foreach ($questions as $questionId => $q) {
        $isRequired = (int)$q['is_required'];
        if ($isRequired === 1) {
            $hasAnswer = false;
            if (isset($submittedAnswersIndex[$questionId])) {
                $ans = $submittedAnswersIndex[$questionId];
                if ($ans['option_id'] !== null || $ans['answer_text'] !== null) {
                    $hasAnswer = true;
                }
            }
            if (!$hasAnswer) {
                $validationErrors[$questionId] = 'This question is required.';
            }
        }
    }

    if ($validationErrors !== []) {
        json_error('Validation failed.', $validationErrors, 422);
    }

    // 5. Database Transaction
    try {
        $connection->begin_transaction();

        if (is_array($existingResponse)) {
            $responseId = (int)$existingResponse['survey_response_id'];
        } else {
            $statement = $connection->prepare(
                'INSERT INTO survey_responses (survey_invitation_id, created_at) VALUES (?, NOW(6))'
            );
            $statement->bind_param('i', $invitationId);
            $statement->execute();
            $responseId = (int)$connection->insert_id;
            $statement->close();
        }

        // Delete any existing answers for this response
        $statement = $connection->prepare('DELETE FROM survey_answers WHERE survey_response_id = ?');
        $statement->bind_param('i', $responseId);
        $statement->execute();
        $statement->close();

        // Insert answers
        $statement = $connection->prepare(
            'INSERT INTO survey_answers (survey_response_id, survey_question_id, survey_question_option_id, answer_text, answer_number) 
             VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($submittedAnswersIndex as $questionId => $ans) {
            $optionId = $ans['option_id'];
            $answerText = $ans['answer_text'];
            
            $answerNumber = null;
            if ($answerText !== null && is_numeric($answerText)) {
                $answerNumber = (float)$answerText;
            }

            $statement->bind_param('iiisd', $responseId, $questionId, $optionId, $answerText, $answerNumber);
            $statement->execute();
        }
        $statement->close();

        // Get SUBMITTED status ID
        $submittedStatusId = get_or_create_response_status($connection, 'SUBMITTED', 'Submitted', 1);

        // Update survey_invitations
        $statement = $connection->prepare(
            'UPDATE survey_invitations 
             SET survey_response_status_id = ?, completed_at = NOW(6) 
             WHERE survey_invitation_id = ?'
        );
        $statement->bind_param('ii', $submittedStatusId, $invitationId);
        $statement->execute();
        $statement->close();

        // Update survey_responses
        $statement = $connection->prepare(
            'UPDATE survey_responses 
             SET submitted_at = NOW(6) 
             WHERE survey_response_id = ?'
        );
        $statement->bind_param('i', $responseId);
        $statement->execute();
        $statement->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Survey response submitted successfully.');
}

function list_my_survey_responses(): never
{
    get_completed_surveys();
}

function get_completed_surveys(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $profile = graduate_surveys_profile($connection, $authenticated['user_id']);
    $graduateProfileId = (int) $profile['graduate_profile_id'];

    $statement = $connection->prepare(
        'SELECT s.survey_id,
                s.name AS title,
                s.name,
                NULL AS description,
                i.completed_at,
                r.submitted_at,
                status.name AS status_name,
                status.code AS status_code
         FROM survey_invitations AS i
         INNER JOIN surveys AS s ON s.survey_id = i.survey_id
         INNER JOIN survey_response_statuses AS status ON status.survey_response_status_id = i.survey_response_status_id
         LEFT JOIN survey_responses AS r ON r.survey_invitation_id = i.survey_invitation_id
         WHERE i.graduate_profile_id = ?
           AND (status.is_final = 1 OR UPPER(status.code) = \'SUBMITTED\' OR r.submitted_at IS NOT NULL)
         ORDER BY r.submitted_at DESC, i.completed_at DESC'
    );
    $statement->bind_param('i', $graduateProfileId);
    $statement->execute();
    $surveys = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    foreach ($surveys as &$survey) {
        $survey['survey_id'] = (int) $survey['survey_id'];
    }
    unset($survey);

    json_success('Completed surveys retrieved successfully.', $surveys);
}

function get_survey_response(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $profile = graduate_surveys_profile($connection, $authenticated['user_id']);
    $graduateProfileId = (int) $profile['graduate_profile_id'];

    $statement = $connection->prepare(
        'SELECT r.survey_response_id,
                r.survey_invitation_id,
                r.submitted_at,
                r.created_at,
                r.updated_at,
                i.survey_id,
                i.graduate_profile_id,
                s.name AS survey_title,
                status.name AS status_name,
                status.code AS status_code
         FROM survey_invitations AS i
         INNER JOIN survey_responses AS r ON r.survey_invitation_id = i.survey_invitation_id
         INNER JOIN surveys AS s ON s.survey_id = i.survey_id
         INNER JOIN survey_response_statuses AS status ON status.survey_response_status_id = i.survey_response_status_id
         WHERE i.survey_id = ? AND i.graduate_profile_id = ?
         LIMIT 1'
    );
    $statement->bind_param('ii', $surveyId, $graduateProfileId);
    $statement->execute();
    $response = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($response)) {
        json_error('Survey response was not found.', [], 404);
    }

    $responseId = (int) $response['survey_response_id'];
    $response['survey_response_id'] = $responseId;
    $response['survey_invitation_id'] = (int) $response['survey_invitation_id'];
    $response['survey_id'] = (int) $response['survey_id'];
    $response['graduate_profile_id'] = (int) $response['graduate_profile_id'];

    $statement = $connection->prepare(
        'SELECT a.survey_answer_id,
                a.survey_question_id AS question_id,
                a.survey_question_option_id AS option_id,
                a.answer_text,
                a.answer_number
         FROM survey_answers AS a
         WHERE a.survey_response_id = ?
         ORDER BY a.survey_answer_id ASC'
    );
    $statement->bind_param('i', $responseId);
    $statement->execute();
    $answers = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    foreach ($answers as &$answer) {
        $answer['survey_answer_id'] = (int) $answer['survey_answer_id'];
        $answer['question_id'] = (int) $answer['question_id'];
        $answer['option_id'] = $answer['option_id'] !== null ? (int) $answer['option_id'] : null;
        $answer['answer_number'] = $answer['answer_number'] !== null ? (float) $answer['answer_number'] : null;
    }
    unset($answer);

    $response['answers'] = $answers;

    json_success('Survey response retrieved successfully.', $response);
}

