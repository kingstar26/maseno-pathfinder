<?php

declare(strict_types=1);

/**
 * Ensure question_types table contains standard type records if empty.
 */
function ensure_question_types_seeded(mysqli $connection): void
{
    $checkStmt = $connection->prepare('SELECT COUNT(*) AS total FROM question_types');
    $checkStmt->execute();
    $res = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (is_array($res) && (int) $res['total'] > 0) {
        return;
    }

    $defaultTypes = [
        ['single_choice', 'Single Choice'],
        ['multiple_choice', 'Multiple Choice'],
        ['short_text', 'Short Text'],
        ['long_text', 'Long Text'],
        ['rating', 'Rating'],
        ['number', 'Number'],
    ];

    $insertStmt = $connection->prepare('INSERT INTO question_types (code, name, is_active) VALUES (?, ?, 1)');
    foreach ($defaultTypes as $type) {
        $code = $type[0];
        $name = $type[1];
        $insertStmt->bind_param('ss', $code, $name);
        $insertStmt->execute();
    }
    $insertStmt->close();
}

/**
 * Safely parse date strings into MySQL datetime standard format.
 */
function parse_survey_datetime(?string $input): ?string
{
    if ($input === null || trim($input) === '') {
        return null;
    }
    try {
        $dt = new DateTimeImmutable(trim($input));
        return $dt->format('Y-m-d H:i:s.u');
    } catch (Throwable) {
        return null;
    }
}

/**
 * Fetch full nested hierarchy for a survey: Survey -> Sections -> Questions -> Options.
 *
 * @return array<string, mixed>|null
 */
function fetch_management_survey_structure(mysqli $connection, int $surveyId): ?array
{
    $stmt = $connection->prepare(
        'SELECT survey_id, name, name AS title, NULL AS description, is_active, available_from, available_until, created_at, updated_at
         FROM surveys
         WHERE survey_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $surveyId);
    $stmt->execute();
    $surveyRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($surveyRow)) {
        return null;
    }

    $secStmt = $connection->prepare(
        'SELECT survey_section_id, survey_id, title, display_order, is_required, created_at, updated_at
         FROM survey_sections
         WHERE survey_id = ?
         ORDER BY display_order ASC, survey_section_id ASC'
    );
    $secStmt->bind_param('i', $surveyId);
    $secStmt->execute();
    $sectionsRaw = $secStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $secStmt->close();

    $sections = [];
    foreach ($sectionsRaw as $sRow) {
        $secId = (int) $sRow['survey_section_id'];

        $qStmt = $connection->prepare(
            'SELECT sq.survey_question_id, sq.survey_section_id, sq.prompt, sq.response_type, sq.is_required, sq.display_order, sq.created_at, sq.updated_at, qt.question_type_id
             FROM survey_questions AS sq
             LEFT JOIN question_types AS qt ON qt.code = sq.response_type
             WHERE sq.survey_section_id = ?
             ORDER BY sq.display_order ASC, sq.survey_question_id ASC'
        );
        $qStmt->bind_param('i', $secId);
        $qStmt->execute();
        $questionsRaw = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $qStmt->close();

        $questions = [];
        foreach ($questionsRaw as $qRow) {
            $qId = (int) $qRow['survey_question_id'];

            $optStmt = $connection->prepare(
                'SELECT survey_question_option_id, survey_question_id, label, value, display_order
                 FROM survey_question_options
                 WHERE survey_question_id = ?
                 ORDER BY display_order ASC, survey_question_option_id ASC'
            );
            $optStmt->bind_param('i', $qId);
            $optStmt->execute();
            $optionsRaw = $optStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $optStmt->close();

            $options = [];
            foreach ($optionsRaw as $oRow) {
                $options[] = [
                    'survey_question_option_id' => (int) $oRow['survey_question_option_id'],
                    'survey_question_id' => (int) $oRow['survey_question_id'],
                    'label' => (string) $oRow['label'],
                    'value' => (string) $oRow['value'],
                    'display_order' => (int) $oRow['display_order'],
                ];
            }

            $questions[] = [
                'survey_question_id' => $qId,
                'survey_section_id' => (int) $qRow['survey_section_id'],
                'prompt' => (string) $qRow['prompt'],
                'response_type' => (string) $qRow['response_type'],
                'question_type_id' => $qRow['question_type_id'] !== null ? (int) $qRow['question_type_id'] : null,
                'is_required' => (int) $qRow['is_required'],
                'display_order' => (int) $qRow['display_order'],
                'options' => $options,
                'created_at' => (string) $qRow['created_at'],
                'updated_at' => (string) $qRow['updated_at'],
            ];
        }

        $sections[] = [
            'survey_section_id' => $secId,
            'survey_id' => (int) $sRow['survey_id'],
            'title' => (string) $sRow['title'],
            'display_order' => (int) $sRow['display_order'],
            'is_required' => (int) $sRow['is_required'],
            'questions' => $questions,
            'created_at' => (string) $sRow['created_at'],
            'updated_at' => (string) $sRow['updated_at'],
        ];
    }

    return [
        'survey_id' => (int) $surveyRow['survey_id'],
        'name' => (string) $surveyRow['name'],
        'title' => (string) $surveyRow['name'],
        'description' => $surveyRow['description'] !== null ? (string) $surveyRow['description'] : null,
        'is_active' => (int) $surveyRow['is_active'],
        'available_from' => $surveyRow['available_from'] !== null ? (string) $surveyRow['available_from'] : null,
        'available_until' => $surveyRow['available_until'] !== null ? (string) $surveyRow['available_until'] : null,
        'sections' => $sections,
        'created_at' => (string) $surveyRow['created_at'],
        'updated_at' => (string) $surveyRow['updated_at'],
    ];
}

/** @return array<int, array<string, mixed>> */
function fetch_management_completed_survey_submissions(mysqli $connection): array
{
    $submissionStmt = $connection->prepare(
        'SELECT
            r.survey_response_id,
            i.survey_id,
            i.graduate_profile_id,
            s.name AS survey_title,
            u.first_name,
            u.last_name,
            u.email AS graduate_email,
            gq.admission_number,
            status.name AS status_name,
            r.submitted_at,
            i.completed_at
         FROM survey_responses AS r
         INNER JOIN survey_invitations AS i ON i.survey_invitation_id = r.survey_invitation_id
         INNER JOIN surveys AS s ON s.survey_id = i.survey_id
         INNER JOIN graduate_profiles AS gp ON gp.graduate_profile_id = i.graduate_profile_id
         INNER JOIN users AS u ON u.user_id = gp.user_id
         LEFT JOIN survey_response_statuses AS status ON status.survey_response_status_id = i.survey_response_status_id
         LEFT JOIN graduate_qualifications AS gq ON gq.graduate_qualification_id = (
             SELECT latest_gq.graduate_qualification_id
             FROM graduate_qualifications AS latest_gq
             WHERE latest_gq.graduate_profile_id = gp.graduate_profile_id
             ORDER BY latest_gq.updated_at DESC, latest_gq.graduate_qualification_id DESC
             LIMIT 1
         )
         WHERE r.submitted_at IS NOT NULL
            OR i.completed_at IS NOT NULL
            OR status.is_final = 1
            OR UPPER(COALESCE(status.code, \'\')) = \'SUBMITTED\'
         ORDER BY r.submitted_at DESC, i.completed_at DESC, r.survey_response_id DESC'
    );
    $submissionStmt->execute();
    $submissions = $submissionStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $submissionStmt->close();

    $answerStmt = $connection->prepare(
        'SELECT
            a.survey_question_id AS question_id,
            q.prompt,
            a.survey_question_option_id AS option_id,
            o.label AS option_label,
            a.answer_text,
            a.answer_number
         FROM survey_answers AS a
         INNER JOIN survey_questions AS q ON q.survey_question_id = a.survey_question_id
         LEFT JOIN survey_question_options AS o ON o.survey_question_option_id = a.survey_question_option_id
         WHERE a.survey_response_id = ?
         ORDER BY q.display_order ASC, a.survey_answer_id ASC'
    );

    foreach ($submissions as &$submission) {
        $responseId = (int) $submission['survey_response_id'];
        $answerStmt->bind_param('i', $responseId);
        $answerStmt->execute();
        $answers = $answerStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($answers as &$answer) {
            $answer['question_id'] = (int) $answer['question_id'];
            $answer['option_id'] = $answer['option_id'] !== null ? (int) $answer['option_id'] : null;
        }
        unset($answer);

        $submission['survey_response_id'] = $responseId;
        $submission['survey_id'] = (int) $submission['survey_id'];
        $submission['graduate_profile_id'] = (int) $submission['graduate_profile_id'];
        $submission['answers'] = $answers;
    }
    unset($submission);
    $answerStmt->close();

    return $submissions;
}

// 1. List Surveys (GET /management/surveys)
function list_management_surveys(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $stmt = $connection->prepare(
        'SELECT 
            s.survey_id,
            s.name,
            s.name AS title,
            NULL AS description,
            s.is_active,
            s.available_from,
            s.available_until,
            s.created_at,
            s.updated_at,
            COUNT(DISTINCT sec.survey_section_id) AS sections_count,
            COUNT(DISTINCT q.survey_question_id) AS questions_count
         FROM surveys AS s
         LEFT JOIN survey_sections AS sec ON sec.survey_id = s.survey_id
         LEFT JOIN survey_questions AS q ON q.survey_section_id = sec.survey_section_id
         GROUP BY s.survey_id, s.name, s.is_active, s.available_from, s.available_until, s.created_at, s.updated_at
         ORDER BY s.created_at DESC, s.survey_id DESC'
    );
    $stmt->execute();
    $rawSurveys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $submissionsBySurvey = [];
    foreach (fetch_management_completed_survey_submissions($connection) as $submission) {
        $submissionsBySurvey[(int) $submission['survey_id']][] = $submission;
    }

    $surveys = [];
    foreach ($rawSurveys as $row) {
        $surveyId = (int) $row['survey_id'];
        $completedSubmissions = $submissionsBySurvey[$surveyId] ?? [];
        $surveys[] = [
            'survey_id' => $surveyId,
            'name' => (string) $row['name'],
            'title' => (string) $row['name'],
            'description' => $row['description'] !== null ? (string) $row['description'] : null,
            'is_active' => (int) $row['is_active'],
            'status' => ((int) $row['is_active']) === 1 ? 'active' : 'inactive',
            'available_from' => $row['available_from'] !== null ? (string) $row['available_from'] : null,
            'available_until' => $row['available_until'] !== null ? (string) $row['available_until'] : null,
            'sections_count' => (int) $row['sections_count'],
            'questions_count' => (int) $row['questions_count'],
            'completed_submissions' => $completedSubmissions,
            'completed_submissions_count' => count($completedSubmissions),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    json_success('Management surveys retrieved successfully.', $surveys);
}

// 2. Create Survey (POST /management/surveys)
function create_management_survey(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $input = request_json_body();
    $name = required_string($input, 'name', 250);
    $availableFrom = parse_survey_datetime(optional_string($input, 'available_from', 50));
    $availableUntil = parse_survey_datetime(optional_string($input, 'available_until', 50));
    $isActive = isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : 0;

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $connection->begin_transaction();
    try {
        $stmt = $connection->prepare(
            'INSERT INTO surveys (name, is_active, available_from, available_until) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('siss', $name, $isActive, $availableFrom, $availableUntil);
        $stmt->execute();
        $surveyId = (int) $connection->insert_id;
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $created = fetch_management_survey_structure($connection, $surveyId);
    json_success('Survey created successfully.', $created, 201);
}

// 3. Update Survey (PUT /management/surveys/{id})
function update_management_survey(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $input = request_json_body();
    $name = required_string($input, 'name', 250);
    $availableFrom = parse_survey_datetime(optional_string($input, 'available_from', 50));
    $availableUntil = parse_survey_datetime(optional_string($input, 'available_until', 50));

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $connection->begin_transaction();
    try {
        $existing = fetch_management_survey_structure($connection, $surveyId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Survey was not found.', [], 404);
        }

        $isActive = isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : (int) $existing['is_active'];

        $stmt = $connection->prepare(
            'UPDATE surveys SET name = ?, is_active = ?, available_from = ?, available_until = ? WHERE survey_id = ?'
        );
        $stmt->bind_param('sissi', $name, $isActive, $availableFrom, $availableUntil, $surveyId);
        $stmt->execute();
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $updated = fetch_management_survey_structure($connection, $surveyId);
    json_success('Survey updated successfully.', $updated);
}

// 4. Delete Survey (DELETE /management/surveys/{id})
function delete_management_survey(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $connection->begin_transaction();
    try {
        $existing = fetch_management_survey_structure($connection, $surveyId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Survey was not found.', [], 404);
        }

        // Check if survey_responses exist for that survey
        $respStmt = $connection->prepare(
            'SELECT COUNT(r.survey_response_id) AS total_responses
             FROM survey_responses AS r
             INNER JOIN survey_invitations AS i ON i.survey_invitation_id = r.survey_invitation_id
             WHERE i.survey_id = ?'
        );
        $respStmt->bind_param('i', $surveyId);
        $respStmt->execute();
        $respRow = $respStmt->get_result()->fetch_assoc();
        $respStmt->close();

        $responseCount = is_array($respRow) ? (int) $respRow['total_responses'] : 0;

        // If responses exist, abort deletion and return HTTP 422
        if ($responseCount > 0) {
            rollback_transaction($connection);
            json_error('Survey cannot be deleted because graduate responses already exist.', [], 422);
        }

        // If no responses exist, delete child records inside transaction
        // a. Delete question options
        $delOptStmt = $connection->prepare(
            'DELETE sqo FROM survey_question_options sqo
             INNER JOIN survey_questions sq ON sq.survey_question_id = sqo.survey_question_id
             INNER JOIN survey_sections ss ON ss.survey_section_id = sq.survey_section_id
             WHERE ss.survey_id = ?'
        );
        $delOptStmt->bind_param('i', $surveyId);
        $delOptStmt->execute();
        $delOptStmt->close();

        // b. Delete questions
        $delQStmt = $connection->prepare(
            'DELETE sq FROM survey_questions sq
             INNER JOIN survey_sections ss ON ss.survey_section_id = sq.survey_section_id
             WHERE ss.survey_id = ?'
        );
        $delQStmt->bind_param('i', $surveyId);
        $delQStmt->execute();
        $delQStmt->close();

        // c. Delete sections
        $delSecStmt = $connection->prepare('DELETE FROM survey_sections WHERE survey_id = ?');
        $delSecStmt->bind_param('i', $surveyId);
        $delSecStmt->execute();
        $delSecStmt->close();

        // d. Delete invitations
        $delInvStmt = $connection->prepare('DELETE FROM survey_invitations WHERE survey_id = ?');
        $delInvStmt->bind_param('i', $surveyId);
        $delInvStmt->execute();
        $delInvStmt->close();

        // e. Delete survey
        $stmt = $connection->prepare('DELETE FROM surveys WHERE survey_id = ?');
        $stmt->bind_param('i', $surveyId);
        $stmt->execute();
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Survey deleted successfully.');
}

// 5. Publish Survey (PUT /management/surveys/{id}/publish)
function publish_management_survey(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $connection->begin_transaction();
    try {
        $existing = fetch_management_survey_structure($connection, $surveyId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Survey was not found.', [], 404);
        }

        $stmt = $connection->prepare('UPDATE surveys SET is_active = 1, updated_at = NOW(6) WHERE survey_id = ?');
        $stmt->bind_param('i', $surveyId);
        $stmt->execute();
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $published = fetch_management_survey_structure($connection, $surveyId);
    $surveyName = $published['name'] ?? $published['title'] ?? 'Graduate Tracer Survey';

    $gradStmt = $connection->prepare(
        'SELECT u.user_id FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE r.code = \'graduate\' AND u.account_status_id = 1'
    );
    if ($gradStmt) {
        $gradStmt->execute();
        $gradRes = $gradStmt->get_result();
        while ($gRow = $gradRes->fetch_assoc()) {
            send_mugts_notification_email(
                (int) $gRow['user_id'],
                'New Survey Available',
                'New Survey Available',
                'A new graduate tracer survey titled "' . htmlspecialchars((string) $surveyName) . '" is now available. Please sign in to complete it.',
                '/survey'
            );
        }
        $gradStmt->close();
    }

    json_success('Survey published successfully.', $published);
}

// 6. Create Section (POST /management/surveys/{id}/sections)
function create_management_survey_section(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $input = request_json_body();
    $title = required_string($input, 'title', 250);
    $displayOrder = isset($input['display_order']) ? (int) $input['display_order'] : 1;
    $isRequired = isset($input['is_required']) ? ($input['is_required'] ? 1 : 0) : 1;

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $connection->begin_transaction();
    try {
        $existing = fetch_management_survey_structure($connection, $surveyId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Survey was not found.', [], 404);
        }

        $stmt = $connection->prepare(
            'INSERT INTO survey_sections (survey_id, title, display_order, is_required) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('isii', $surveyId, $title, $displayOrder, $isRequired);
        $stmt->execute();
        $sectionId = (int) $connection->insert_id;
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $createdSection = [
        'survey_section_id' => $sectionId,
        'survey_id' => $surveyId,
        'title' => $title,
        'display_order' => $displayOrder,
        'is_required' => $isRequired,
        'questions' => [],
    ];

    json_success('Survey section created successfully.', $createdSection, 201);
}

// 7. Create Question (POST /management/surveys/{id}/questions)
function create_management_survey_question(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $input = request_json_body();
    $prompt = required_string($input, 'prompt', 2000);
    $sectionIdInput = isset($input['section_id']) ? (int) $input['section_id'] : 0;
    $questionTypeIdInput = isset($input['question_type_id']) ? (int) $input['question_type_id'] : 0;
    $responseTypeInput = optional_string($input, 'response_type', 30);
    $isRequired = isset($input['is_required']) ? ($input['is_required'] ? 1 : 0) : 1;
    $displayOrder = isset($input['display_order']) ? (int) $input['display_order'] : 1;

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);
    ensure_question_types_seeded($connection);

    $connection->begin_transaction();
    try {
        $survey = fetch_management_survey_structure($connection, $surveyId);
        if ($survey === null) {
            rollback_transaction($connection);
            json_error('Survey was not found.', [], 404);
        }

        // Validate section_id foreign key against survey
        $sectionId = 0;
        if ($sectionIdInput > 0) {
            $secStmt = $connection->prepare(
                'SELECT survey_section_id FROM survey_sections WHERE survey_section_id = ? AND survey_id = ? LIMIT 1'
            );
            $secStmt->bind_param('ii', $sectionIdInput, $surveyId);
            $secStmt->execute();
            $secRow = $secStmt->get_result()->fetch_assoc();
            $secStmt->close();

            if (!is_array($secRow)) {
                rollback_transaction($connection);
                json_error('The specified survey section was not found for this survey.', ['section_id' => 'Invalid section ID for this survey.'], 422);
            }
            $sectionId = (int) $secRow['survey_section_id'];
        } else {
            if (!empty($survey['sections'])) {
                $sectionId = (int) $survey['sections'][0]['survey_section_id'];
            } else {
                $secStmt = $connection->prepare(
                    'INSERT INTO survey_sections (survey_id, title, display_order, is_required) VALUES (?, \'General Questions\', 1, 1)'
                );
                $secStmt->bind_param('i', $surveyId);
                $secStmt->execute();
                $sectionId = (int) $connection->insert_id;
                $secStmt->close();
            }
        }

        // Validate question_type_id or response_type input against question_types table
        $responseType = null;
        $questionTypeId = null;

        if ($questionTypeIdInput > 0) {
            $qtStmt = $connection->prepare('SELECT question_type_id, code FROM question_types WHERE question_type_id = ? AND is_active = 1 LIMIT 1');
            $qtStmt->bind_param('i', $questionTypeIdInput);
            $qtStmt->execute();
            $qtRow = $qtStmt->get_result()->fetch_assoc();
            $qtStmt->close();

            if (is_array($qtRow) && !empty($qtRow['code'])) {
                $responseType = (string) $qtRow['code'];
                $questionTypeId = (int) $qtRow['question_type_id'];
            } else {
                rollback_transaction($connection);
                json_error('Validation failed.', ['question_type_id' => 'The selected question type is invalid or inactive.'], 422);
            }
        } elseif ($responseTypeInput !== null) {
            $qtStmt = $connection->prepare('SELECT question_type_id, code FROM question_types WHERE code = ? AND is_active = 1 LIMIT 1');
            $qtStmt->bind_param('s', $responseTypeInput);
            $qtStmt->execute();
            $qtRow = $qtStmt->get_result()->fetch_assoc();
            $qtStmt->close();

            if (is_array($qtRow) && !empty($qtRow['code'])) {
                $responseType = (string) $qtRow['code'];
                $questionTypeId = (int) $qtRow['question_type_id'];
            } else {
                rollback_transaction($connection);
                json_error('Validation failed.', ['question_type_id' => 'Invalid question type specified.'], 422);
            }
        } else {
            $responseType = 'long_text';
        }

        $stmt = $connection->prepare(
            'INSERT INTO survey_questions (survey_section_id, prompt, response_type, is_required, display_order) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issii', $sectionId, $prompt, $responseType, $isRequired, $displayOrder);
        $stmt->execute();
        $questionId = (int) $connection->insert_id;
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $createdQuestion = [
        'survey_question_id' => $questionId,
        'survey_section_id' => $sectionId,
        'prompt' => $prompt,
        'response_type' => $responseType,
        'question_type_id' => $questionTypeId,
        'is_required' => $isRequired,
        'display_order' => $displayOrder,
        'options' => [],
    ];

    json_success('Survey question created successfully.', $createdQuestion, 201);
}

// 8. Add Question Options (POST /management/questions/{id}/options)
function create_management_question_option(int $questionId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $input = request_json_body();
    $label = required_string($input, 'label', 250);
    $valueInput = optional_string($input, 'value', 250);
    $value = $valueInput ?? strtolower(trim((string) preg_replace('/[^A-Za-z0-9_]+/', '_', $label)));
    $displayOrder = isset($input['display_order']) ? (int) $input['display_order'] : 1;

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $connection->begin_transaction();
    try {
        $qStmt = $connection->prepare('SELECT survey_question_id FROM survey_questions WHERE survey_question_id = ? LIMIT 1');
        $qStmt->bind_param('i', $questionId);
        $qStmt->execute();
        $qRow = $qStmt->get_result()->fetch_assoc();
        $qStmt->close();

        if (!is_array($qRow)) {
            rollback_transaction($connection);
            json_error('Survey question was not found.', [], 404);
        }

        $stmt = $connection->prepare(
            'INSERT INTO survey_question_options (survey_question_id, label, value, display_order) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('issi', $questionId, $label, $value, $displayOrder);
        $stmt->execute();
        $optionId = (int) $connection->insert_id;
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $createdOption = [
        'survey_question_option_id' => $optionId,
        'survey_question_id' => $questionId,
        'label' => $label,
        'value' => $value,
        'display_order' => $displayOrder,
    ];

    json_success('Survey question option added successfully.', $createdOption, 201);
}

// 9. View Survey Structure (GET /management/surveys/{id})
function get_management_survey_details(int $surveyId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $survey = fetch_management_survey_structure($connection, $surveyId);
    if ($survey === null) {
        json_error('Survey was not found.', [], 404);
    }

    json_success('Survey structure retrieved successfully.', $survey);
}
