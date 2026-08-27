<?php

declare(strict_types=1);

/**
 * Fetch detailed data for a recommendation request.
 *
 * @return array<string, mixed>|null
 */
function fetch_management_recommendation_request(mysqli $connection, int $requestId): ?array
{
    $statement = $connection->prepare(
        'SELECT 
            rr.recommendation_request_id,
            rr.graduate_profile_id,
            rr.purpose,
            rr.request_status,
            rr.requested_at,
            rr.processed_at,
            rr.processed_by,
            rr.created_at,
            rr.updated_at,
            u.user_id AS graduate_user_id,
            u.first_name,
            u.last_name,
            u.email,
            pp.middle_name,
            pp.phone_number,
            pp.national_id,
            gq.admission_number,
            gq.registration_number,
            p.name AS programme_name
         FROM recommendation_requests AS rr
         INNER JOIN graduate_profiles AS gp ON gp.graduate_profile_id = rr.graduate_profile_id
         INNER JOIN users AS u ON u.user_id = gp.user_id
         LEFT JOIN person_profiles AS pp ON pp.user_id = u.user_id
         LEFT JOIN graduate_qualifications AS gq ON gq.graduate_profile_id = gp.graduate_profile_id
         LEFT JOIN programmes AS p ON p.programme_id = gq.programme_id
         WHERE rr.recommendation_request_id = ?
         LIMIT 1'
    );
    $statement->bind_param('i', $requestId);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($row)) {
        return null;
    }

    $reqId = (int) $row['recommendation_request_id'];

    $letterStmt = $connection->prepare(
        'SELECT 
            rl.recommendation_letter_id,
            rl.recommendation_request_id,
            rl.issued_by,
            rl.file_id,
            rl.letter_title,
            rl.issued_date,
            rl.created_at,
            rl.updated_at,
            f.original_filename,
            f.mime_type,
            f.byte_size,
            f.storage_key,
            pu.first_name AS processor_first_name,
            pu.last_name AS processor_last_name
         FROM recommendation_letters AS rl
         LEFT JOIN files AS f ON f.file_id = rl.file_id
         LEFT JOIN management_profiles AS mp ON mp.management_profile_id = rl.issued_by
         LEFT JOIN users AS pu ON pu.user_id = mp.user_id
         WHERE rl.recommendation_request_id = ?
         ORDER BY rl.issued_date DESC, rl.recommendation_letter_id DESC'
    );
    $letterStmt->bind_param('i', $reqId);
    $letterStmt->execute();
    $rawLetters = $letterStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $letterStmt->close();

    $letters = [];
    $primaryLetterFile = null;

    foreach ($rawLetters as $lRow) {
        $letterItem = [
            'recommendation_letter_id' => (int) $lRow['recommendation_letter_id'],
            'recommendation_request_id' => (int) $lRow['recommendation_request_id'],
            'issued_by' => (int) $lRow['issued_by'],
            'issued_by_name' => trim(($lRow['processor_first_name'] ?? '') . ' ' . ($lRow['processor_last_name'] ?? '')),
            'file_id' => $lRow['file_id'] !== null ? (int) $lRow['file_id'] : null,
            'letter_title' => $lRow['letter_title'] !== null ? (string) $lRow['letter_title'] : null,
            'issued_date' => (string) $lRow['issued_date'],
            'created_at' => (string) $lRow['created_at'],
            'updated_at' => (string) $lRow['updated_at'],
            'original_filename' => $lRow['original_filename'] !== null ? (string) $lRow['original_filename'] : null,
            'mime_type' => $lRow['mime_type'] !== null ? (string) $lRow['mime_type'] : null,
            'byte_size' => $lRow['byte_size'] !== null ? (int) $lRow['byte_size'] : null,
            'download_url' => '/graduate/recommendations/' . $reqId . '/download',
        ];
        $letters[] = $letterItem;
        if ($primaryLetterFile === null) {
            $primaryLetterFile = $letterItem;
        }
    }

    $graduateFullName = trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

    return [
        'request_id' => $reqId,
        'recommendation_request_id' => $reqId,
        'graduate_profile_id' => (int) $row['graduate_profile_id'],
        'graduate_user_id' => (int) $row['graduate_user_id'],
        'graduate_name' => $graduateFullName,
        'graduate_email' => (string) $row['email'],
        'graduate_phone' => $row['phone_number'] !== null ? (string) $row['phone_number'] : null,
        'admission_number' => $row['admission_number'] !== null ? (string) $row['admission_number'] : null,
        'registration_number' => $row['registration_number'] !== null ? (string) $row['registration_number'] : null,
        'programme' => $row['programme_name'] !== null ? (string) $row['programme_name'] : null,
        'purpose' => (string) $row['purpose'],
        'status' => (string) $row['request_status'],
        'request_status' => (string) $row['request_status'],
        'request_date' => (string) $row['requested_at'],
        'requested_at' => (string) $row['requested_at'],
        'approved_date' => $row['processed_at'] !== null ? (string) $row['processed_at'] : null,
        'processed_at' => $row['processed_at'] !== null ? (string) $row['processed_at'] : null,
        'processed_by' => $row['processed_by'] !== null ? (int) $row['processed_by'] : null,
        'letter_file' => $primaryLetterFile,
        'letters' => $letters,
        'created_at' => (string) $row['created_at'],
        'updated_at' => (string) $row['updated_at'],
    ];
}

function list_management_recommendations(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $statusFilter = isset($_GET['status']) ? trim((string) $_GET['status']) : null;

    if ($statusFilter !== null && $statusFilter !== '') {
        $statement = $connection->prepare(
            'SELECT recommendation_request_id FROM recommendation_requests
             WHERE LOWER(request_status) = LOWER(?)
             ORDER BY requested_at DESC, recommendation_request_id DESC'
        );
        $statement->bind_param('s', $statusFilter);
    } else {
        $statement = $connection->prepare(
            'SELECT recommendation_request_id FROM recommendation_requests
             ORDER BY requested_at DESC, recommendation_request_id DESC'
        );
    }

    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    $requests = [];
    foreach ($rows as $r) {
        $req = fetch_management_recommendation_request($connection, (int) $r['recommendation_request_id']);
        if ($req !== null) {
            $requests[] = $req;
        }
    }

    json_success('Management recommendation requests retrieved successfully.', $requests);
}

function get_management_recommendation_details(int $requestId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $request = fetch_management_recommendation_request($connection, $requestId);
    if ($request === null) {
        json_error('Recommendation request was not found.', [], 404);
    }

    json_success('Recommendation request details retrieved successfully.', $request);
}

/**
 * Load an image for the hand-built PDF. PNG images are normalised to RGB (and a
 * soft mask where needed), while JPEG files can be embedded as-is. This keeps
 * the letter generator independent of optional PHP image extensions on XAMPP.
 *
 * @return array{width:int,height:int,color_space:string,filter:string,data:string,alpha_data?:string}|null
 */
function load_recommendation_pdf_image(string $path, string $label): ?array
{
    if (!file_exists($path)) {
        error_log("Recommendation PDF asset unavailable ({$label}): {$path}");
        return null;
    }
    if (!is_file($path) || !is_readable($path)) {
        error_log("Recommendation PDF asset is not readable ({$label}): {$path}");
        return null;
    }

    $imageInfo = @getimagesize($path);
    if (!is_array($imageInfo)) {
        error_log("Recommendation PDF asset is not a valid image ({$label}): {$path}");
        return null;
    }

    $contents = @file_get_contents($path);
    if ($contents === false) {
        error_log("Recommendation PDF asset could not be read ({$label}): {$path}");
        return null;
    }

    if (($imageInfo[2] ?? null) === IMAGETYPE_JPEG) {
        return [
            'width' => (int) $imageInfo[0],
            'height' => (int) $imageInfo[1],
            'color_space' => '/DeviceRGB',
            'filter' => '/DCTDecode',
            'data' => $contents,
        ];
    }

    // Support the standard non-interlaced 8-bit RGB/RGBA PNGs used for the
    // test signature and stamp. Unsupported assets simply leave their space blank.
    if (($imageInfo[2] ?? null) !== IMAGETYPE_PNG || strlen($contents) < 33) {
        error_log("Recommendation PDF asset format is unsupported ({$label}): {$path}");
        return null;
    }

    $header = unpack('Nwidth/Nheight/Cbit_depth/Ccolor_type/Ccompression/Cfilter/Cinterlace', substr($contents, 16, 13));
    if (!is_array($header) || $header['bit_depth'] !== 8 || !in_array($header['color_type'], [2, 6], true)
        || $header['compression'] !== 0 || $header['filter'] !== 0 || $header['interlace'] !== 0) {
        error_log("Recommendation PNG asset has unsupported encoding ({$label}): {$path}");
        return null;
    }

    $idat = '';
    $offset = 8;
    while ($offset + 12 <= strlen($contents)) {
        $lengthData = unpack('Nlength', substr($contents, $offset, 4));
        $length = (int) ($lengthData['length'] ?? -1);
        if ($length < 0 || $offset + 12 + $length > strlen($contents)) {
            error_log("Recommendation PNG asset is malformed ({$label}): {$path}");
            return null;
        }
        $type = substr($contents, $offset + 4, 4);
        if ($type === 'IDAT') {
            $idat .= substr($contents, $offset + 8, $length);
        }
        if ($type === 'IEND') {
            break;
        }
        $offset += 12 + $length;
    }

    $decoded = @gzuncompress($idat);
    $channels = $header['color_type'] === 6 ? 4 : 3;
    $rowBytes = $header['width'] * $channels;
    if ($decoded === false || strlen($decoded) !== ($rowBytes + 1) * $header['height']) {
        error_log("Recommendation PNG asset could not be decoded ({$label}): {$path}");
        return null;
    }

    $previous = array_fill(0, $rowBytes, 0);
    $rgb = '';
    $alpha = '';
    $position = 0;
    for ($y = 0; $y < $header['height']; $y++) {
        $filterType = ord($decoded[$position++]);
        $row = array_values(unpack('C*', substr($decoded, $position, $rowBytes)));
        $position += $rowBytes;
        for ($x = 0; $x < $rowBytes; $x++) {
            $left = $x >= $channels ? $row[$x - $channels] : 0;
            $up = $previous[$x];
            $upLeft = $x >= $channels ? $previous[$x - $channels] : 0;
            if ($filterType === 1) {
                $row[$x] = ($row[$x] + $left) & 255;
            } elseif ($filterType === 2) {
                $row[$x] = ($row[$x] + $up) & 255;
            } elseif ($filterType === 3) {
                $row[$x] = ($row[$x] + intdiv($left + $up, 2)) & 255;
            } elseif ($filterType === 4) {
                $p = $left + $up - $upLeft;
                $pa = abs($p - $left);
                $pb = abs($p - $up);
                $pc = abs($p - $upLeft);
                $row[$x] = ($row[$x] + ($pa <= $pb && $pa <= $pc ? $left : ($pb <= $pc ? $up : $upLeft))) & 255;
            } elseif ($filterType !== 0) {
                error_log("Recommendation PNG asset has an invalid filter ({$label}): {$path}");
                return null;
            }
        }
        for ($x = 0; $x < $rowBytes; $x += $channels) {
            $rgb .= chr($row[$x]) . chr($row[$x + 1]) . chr($row[$x + 2]);
            if ($channels === 4) {
                $alpha .= chr($row[$x + 3]);
            }
        }
        $previous = $row;
    }

    $image = [
        'width' => (int) $header['width'],
        'height' => (int) $header['height'],
        'color_space' => '/DeviceRGB',
        'filter' => '/FlateDecode',
        'data' => gzcompress($rgb, 6),
    ];
    if ($alpha !== '') {
        $image['alpha_data'] = gzcompress($alpha, 6);
    }
    return $image;
}

/**
 * Split the stored purpose blob into labeled fields for PDF presentation only.
 *
 * @return array{purpose:string,organisation:string,position:string,deadline:string,instructions:string}
 */
function parse_test_recommendation_purpose_fields(string $purposeRaw): array
{
    $fields = [
        'purpose' => '',
        'organisation' => '',
        'position' => '',
        'deadline' => '',
        'instructions' => '',
    ];
    $unlabeled = [];
    $parts = preg_split('/\s*\|\s*/', $purposeRaw) ?: [];

    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part === '') {
            continue;
        }
        if (preg_match('/^Purpose:\s*(.*)$/i', $part, $match) === 1) {
            $fields['purpose'] = trim((string) $match[1]);
            continue;
        }
        if (preg_match('/^(Organisation\s*\/\s*Institution|Organisation\/institution|Institution):\s*(.*)$/i', $part, $match) === 1) {
            $fields['organisation'] = trim((string) $match[2]);
            continue;
        }
        if (preg_match('/^(Position\s*\/\s*Programme|Position\/programme):\s*(.*)$/i', $part, $match) === 1) {
            $fields['position'] = trim((string) $match[2]);
            continue;
        }
        if (preg_match('/^Deadline:\s*(.*)$/i', $part, $match) === 1) {
            $fields['deadline'] = trim((string) $match[1]);
            continue;
        }
        if (preg_match('/^(Additional\s+)?Instructions?:\s*(.*)$/i', $part, $match) === 1) {
            $fields['instructions'] = trim((string) $match[2]);
            continue;
        }
        $unlabeled[] = $part;
    }

    if ($fields['purpose'] === '') {
        $fields['purpose'] = $unlabeled !== [] ? implode(' | ', $unlabeled) : trim($purposeRaw);
    }

    return $fields;
}

function format_test_recommendation_deadline(string $deadline): string
{
    $deadline = trim($deadline);
    if ($deadline === '') {
        return 'Not specified';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $deadline, $match) === 1) {
        $timestamp = strtotime($deadline);
        if ($timestamp !== false) {
            return date('j F Y', $timestamp);
        }
    }

    return $deadline;
}

function generate_test_recommendation_pdf_content(array $data): string
{
    $reqId = (int) ($data['request_id'] ?? $data['recommendation_request_id'] ?? 0);
    $name = trim((string) ($data['graduate_name'] ?? 'Graduate'));
    if ($name === '') {
        $name = 'Graduate Candidate';
    }
    $admNo = trim((string) ($data['admission_number'] ?? $data['registration_number'] ?? 'N/A'));
    $programme = trim((string) ($data['programme'] ?? 'Undergraduate Degree Programme'));
    $purposeRaw = trim((string) ($data['purpose'] ?? 'General Application'));
    $purposeFields = parse_test_recommendation_purpose_fields($purposeRaw);
    $email = trim((string) ($data['graduate_email'] ?? 'N/A'));
    $dateStr = date('F j, Y');
    $refNo = 'MUGTS/REC/' . date('Y') . '/' . sprintf('%05d', $reqId);

    $pdfEscape = static function (string $text): string {
        $text = str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $text);
        return (string) preg_replace('/[^\x20-\x7E]/', '', $text);
    };

    $wrapPdfText = static function (string $text, int $maxChars) use ($pdfEscape): array {
        $text = trim($text);
        if ($text === '') {
            return ['Not specified'];
        }
        $escaped = $pdfEscape($text);
        $words = preg_split('/\s+/', $escaped) ?: [$escaped];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (strlen($candidate) <= $maxChars) {
                $current = $candidate;
                continue;
            }
            if ($current !== '') {
                $lines[] = $current;
            }
            $current = $word;
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? ['Not specified'] : $lines;
    };

    $requestRows = [
        ['Purpose:', $wrapPdfText($purposeFields['purpose'] !== '' ? $purposeFields['purpose'] : 'General Application', 62)],
        ['Organisation / Institution:', $wrapPdfText($purposeFields['organisation'], 48)],
        ['Position / Programme:', $wrapPdfText($purposeFields['position'], 52)],
        ['Deadline:', $wrapPdfText(format_test_recommendation_deadline($purposeFields['deadline']), 62)],
        ['Additional Instructions:', $wrapPdfText($purposeFields['instructions'], 50)],
    ];

    $escName = $pdfEscape($name);
    $escAdm = $pdfEscape($admNo);
    $escProg = $pdfEscape($programme);
    $escEmail = $pdfEscape($email);
    $escRef = $pdfEscape($refNo);
    $escDate = $pdfEscape($dateStr);

    // These are the exact assets currently supplied in backend/assets/recommendation.
    // Do not substitute placeholders: an incomplete test letter must fail clearly.
    $assetPaths = [
        'university logo' => __DIR__ . '/../assets/recommendation/logo.jpg.jpeg',
        'test signature' => __DIR__ . '/../assets/recommendation/my-signature-1787307009030.png',
        'test stamp' => __DIR__ . '/../assets/recommendation/stamp.png',
    ];
    $images = [];
    foreach ($assetPaths as $label => $path) {
        if (!file_exists($path)) {
            $message = "Required recommendation PDF asset is missing ({$label}): {$path}";
            error_log($message);
            throw new RuntimeException($message);
        }
        $image = load_recommendation_pdf_image($path, $label);
        if ($image === null) {
            $message = "Required recommendation PDF asset could not be embedded ({$label}): {$path}";
            error_log($message);
            throw new RuntimeException($message);
        }
        $images[$label] = $image;
    }
    $logoImage = $images['university logo'];
    $signatureImage = $images['test signature'];
    $stampImage = $images['test stamp'];

    $streamLines = [];
    $streamLines[] = 'q';

    // Subtle diagonal watermark: bottom-left toward top-right.
    $streamLines[] = 'q';
    $streamLines[] = '/GSWm gs';
    $streamLines[] = '0.10 0.26 0.46 rg';
    $streamLines[] = 'BT';
    $streamLines[] = '/F1 46 Tf';
    $streamLines[] = '0.8480 0.5299 -0.5299 0.8480 32 72 Tm';
    $streamLines[] = '(MASENO UNIVERSITY) Tj';
    $streamLines[] = 'ET';
    $streamLines[] = 'Q';

    // Red Header Banner for TEST / DEMO Document
    $streamLines[] = '0.85 0.15 0.15 rg';
    $streamLines[] = '40 742 532 26 re f';

    $streamLines[] = 'BT';
    $streamLines[] = '/F1 12 Tf';
    $streamLines[] = '1 1 1 rg';
    $streamLines[] = '58 751 Td';
    $streamLines[] = '(*** TEST / DEMO RECOMMENDATION LETTER - NOT OFFICIAL ***) Tj';
    $streamLines[] = 'ET';

    // University letterhead
    $streamLines[] = 'q 62 0 0 58 42 668 cm /ImLogo Do Q';
    $streamLines[] = 'BT';
    $streamLines[] = '0.08 0.24 0.45 rg';
    $streamLines[] = '/F1 19 Tf';
    $streamLines[] = '115 712 Td';
    $streamLines[] = '(MASENO UNIVERSITY) Tj';
    $streamLines[] = '/F1 9 Tf';
    $streamLines[] = '0 -14 Td';
    $streamLines[] = '(OFFICE OF THE ACADEMIC REGISTRAR) Tj';
    $streamLines[] = '/F2 9 Tf';
    $streamLines[] = '0 -16 Td';
    $streamLines[] = '(Graduate Tracer System | Records and Verification Office) Tj';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(Private Bag, Maseno, Kenya | Email: info@maseno.ac.ke | Web: www.maseno.ac.ke) Tj';
    $streamLines[] = 'ET';

    // Horizontal Divider Rule
    $streamLines[] = '0.7 0.7 0.7 RG';
    $streamLines[] = '1 w';
    $streamLines[] = '40 652 m 572 652 l S';

    // Reference & Date
    $streamLines[] = 'BT';
    $streamLines[] = '0 0 0 rg';
    $streamLines[] = '/F1 10 Tf';
    $streamLines[] = '40 632 Td';
    $streamLines[] = '(REF: ' . $escRef . ') Tj';
    $streamLines[] = '340 0 Td';
    $streamLines[] = '(Date: ' . $escDate . ') Tj';
    $streamLines[] = 'ET';

    // Document Title
    $streamLines[] = 'BT';
    $streamLines[] = '/F1 13 Tf';
    $streamLines[] = '0.08 0.24 0.45 rg';
    $streamLines[] = '148 606 Td';
    $streamLines[] = '(RECOMMENDATION LETTER - TEST VERSION) Tj';
    $streamLines[] = 'ET';

    // Graduate Information Panel
    $streamLines[] = '0.95 0.95 0.95 rg';
    $streamLines[] = '40 510 532 82 re f';
    $streamLines[] = '0.8 0.8 0.8 RG';
    $streamLines[] = '40 510 532 82 re S';

    $streamLines[] = 'BT';
    $streamLines[] = '0 0 0 rg';
    $streamLines[] = '/F1 10 Tf';
    $streamLines[] = '52 574 Td';
    $streamLines[] = '(CANDIDATE DETAILS) Tj';
    $streamLines[] = '/F2 10 Tf';
    $streamLines[] = '0 -15 Td';
    $streamLines[] = '(Full Name: ' . $escName . ') Tj';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(Admission / Reg No: ' . $escAdm . ') Tj';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(Degree Programme: ' . $escProg . ') Tj';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(Email Address: ' . $escEmail . ') Tj';
    $streamLines[] = 'ET';

    // Recommendation Body
    $streamLines[] = 'BT';
    $streamLines[] = '/F1 11 Tf';
    $streamLines[] = '0 0 0 rg';
    $streamLines[] = '40 488 Td';
    $streamLines[] = '(TO WHOM IT MAY CONCERN) Tj';
    $streamLines[] = '/F2 10 Tf';
    $streamLines[] = '0 -18 Td';
    $streamLines[] = '(This TEST document confirms that ' . $escName . ' is a registered graduate of) Tj';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(Maseno University. The candidate has submitted an official recommendation request,) Tj';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(the particulars of which are recorded below.) Tj';
    $streamLines[] = 'ET';

    $requestLineCount = 0;
    foreach ($requestRows as $requestRow) {
        $requestLineCount += count($requestRow[1]);
    }
    $requestBoxHeight = 22 + ($requestLineCount * 13) + ((count($requestRows) - 1) * 3) + 12;
    $requestBoxTop = 430;
    $requestBoxBottom = $requestBoxTop - $requestBoxHeight;

    $streamLines[] = '0.97 0.98 0.99 rg';
    $streamLines[] = '40 ' . $requestBoxBottom . ' 532 ' . $requestBoxHeight . ' re f';
    $streamLines[] = '0.72 0.78 0.86 RG';
    $streamLines[] = '0.6 w';
    $streamLines[] = '40 ' . $requestBoxBottom . ' 532 ' . $requestBoxHeight . ' re S';
    $streamLines[] = '0.08 0.24 0.45 RG';
    $streamLines[] = '1.2 w';
    $streamLines[] = '40 ' . $requestBoxBottom . ' m 40 ' . $requestBoxTop . ' l S';

    $rowY = $requestBoxTop - 16;
    $streamLines[] = 'BT';
    $streamLines[] = '0.08 0.24 0.45 rg';
    $streamLines[] = '/F1 10 Tf';
    $streamLines[] = '52 ' . $rowY . ' Td';
    $streamLines[] = '(RECOMMENDATION REQUEST) Tj';
    $streamLines[] = 'ET';
    $rowY -= 18;

    foreach ($requestRows as $requestRow) {
        $label = $requestRow[0];
        $valueLines = $requestRow[1];
        $streamLines[] = 'BT';
        $streamLines[] = '0.18 0.22 0.28 rg';
        $streamLines[] = '/F1 9 Tf';
        $streamLines[] = '52 ' . $rowY . ' Td';
        $streamLines[] = '(' . $pdfEscape($label) . ') Tj';
        $streamLines[] = 'ET';
        $streamLines[] = 'BT';
        $streamLines[] = '0 0 0 rg';
        $streamLines[] = '/F2 9 Tf';
        $streamLines[] = '220 ' . $rowY . ' Td';
        $streamLines[] = '(' . $valueLines[0] . ') Tj';
        $streamLines[] = 'ET';
        $rowY -= 13;
        for ($lineIndex = 1, $lineCount = count($valueLines); $lineIndex < $lineCount; $lineIndex++) {
            $streamLines[] = 'BT';
            $streamLines[] = '0 0 0 rg';
            $streamLines[] = '/F2 9 Tf';
            $streamLines[] = '220 ' . $rowY . ' Td';
            $streamLines[] = '(' . $valueLines[$lineIndex] . ') Tj';
            $streamLines[] = 'ET';
            $rowY -= 13;
        }
        $rowY -= 3;
    }

    $bodyY = $requestBoxBottom - 22;
    $streamLines[] = 'BT';
    $streamLines[] = '0 0 0 rg';
    $streamLines[] = '/F2 10 Tf';
    $streamLines[] = '40 ' . $bodyY . ' Td';
    $streamLines[] = '(Based on verified academic records maintained in the Maseno University Graduate) Tj';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(Tracer System, the candidate successfully completed their studies and met all degree) Tj';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(requirements for the ' . $escProg . '.) Tj';
    $streamLines[] = '0 -16 Td';
    $streamLines[] = '(We confirm that the graduate demonstrated good academic standing, integrity, and) Tj';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(commitment during their study period at Maseno University.) Tj';
    $streamLines[] = 'ET';

    $bodyEndY = $bodyY - 68;
    $signatureBoxY = max(36, $bodyEndY - 128);
    $streamLines[] = '0.92 0.95 0.98 rg';
    $streamLines[] = '40 ' . $signatureBoxY . ' 532 108 re f';
    $streamLines[] = '0.7 0.8 0.9 RG';
    $streamLines[] = '0.8 w';
    $streamLines[] = '40 ' . $signatureBoxY . ' 532 108 re S';

    $streamLines[] = 'q 145 0 0 48 55 ' . ($signatureBoxY + 44) . ' cm /ImSignature Do Q';
    $streamLines[] = 'q 108 0 0 72 410 ' . ($signatureBoxY + 28) . ' cm /ImStamp Do Q';

    $streamLines[] = 'BT';
    $streamLines[] = '0 0 0 rg';
    $streamLines[] = '/F2 8 Tf';
    $streamLines[] = '55 ' . ($signatureBoxY + 30) . ' Td';
    $streamLines[] = '(TEST SIGNATURE - ACADEMIC REGISTRAR) Tj';
    $streamLines[] = '/F2 9 Tf';
    $streamLines[] = '0 -13 Td';
    $streamLines[] = '(Dr. TEST Registrar, PhD) Tj';
    $streamLines[] = '0 -12 Td';
    $streamLines[] = '(Academic Registrar, Maseno University) Tj';
    $streamLines[] = 'ET';

    $streamLines[] = 'Q';

    $streamContent = implode("\n", $streamLines);
    $objects = [];
    $objects[1] = "<</Type /Catalog /Pages 2 0 R>>";
    $objects[2] = "<</Type /Pages /Kids [3 0 R] /Count 1>>";
    $objects[4] = "<</Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold>>";
    $objects[5] = "<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>";
    $objects[6] = "<</Type /ExtGState /ca 0.10 /CA 0.10>>";
    $nextObject = 7;
    $xObjectResources = [];
    $addImageObject = static function (array $image, string $resourceName) use (&$objects, &$nextObject, &$xObjectResources): void {
        $imageObject = $nextObject++;
        $smask = '';
        if (isset($image['alpha_data'])) {
            $maskObject = $nextObject++;
            $maskData = $image['alpha_data'];
            $objects[$maskObject] = "<</Type /XObject /Subtype /Image /Width {$image['width']} /Height {$image['height']} /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($maskData) . ">>\nstream\n{$maskData}\nendstream";
            $smask = " /SMask {$maskObject} 0 R";
        }
        $imageData = $image['data'];
        $objects[$imageObject] = "<</Type /XObject /Subtype /Image /Width {$image['width']} /Height {$image['height']} /ColorSpace {$image['color_space']} /BitsPerComponent 8 /Filter {$image['filter']}{$smask} /Length " . strlen($imageData) . ">>\nstream\n{$imageData}\nendstream";
        $xObjectResources[] = "/{$resourceName} {$imageObject} 0 R";
    };
    $addImageObject($logoImage, 'ImLogo');
    $addImageObject($signatureImage, 'ImSignature');
    $addImageObject($stampImage, 'ImStamp');
    $contentsObject = $nextObject++;
    $streamLength = strlen($streamContent);
    $objects[$contentsObject] = "<</Length {$streamLength}>>\nstream\n{$streamContent}\nendstream";
    $xObjects = $xObjectResources === [] ? '' : ' /XObject <<' . implode(' ', $xObjectResources) . '>>';
    $objects[3] = "<</Type /Page /Parent 2 0 R /Resources <</Font <</F1 4 0 R /F2 5 0 R>> /ExtGState <</GSWm 6 0 R>>{$xObjects}>> /MediaBox [0 0 612 792] /Contents {$contentsObject} 0 R>>";

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [];

    foreach ($objects as $num => $objContent) {
        $offsets[$num] = strlen($pdf);
        $pdf .= "{$num} 0 obj\n{$objContent}\nendobj\n";
    }

    $startXref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<</Size " . (count($objects) + 1) . " /Root 1 0 R>>\n";
    $pdf .= "startxref\n{$startXref}\n%%EOF";

    return $pdf;
}

function approve_recommendation_request(int $requestId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    $managementProfileId = management_profile_id($connection, $userId);

    $connection->begin_transaction();
    try {
        $existing = fetch_management_recommendation_request($connection, $requestId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Recommendation request was not found.', [], 404);
        }

        $stmt = $connection->prepare(
            'UPDATE recommendation_requests SET request_status = \'Approved\', processed_at = NOW(6), processed_by = ? WHERE recommendation_request_id = ?'
        );
        $stmt->bind_param('ii', $managementProfileId, $requestId);
        $stmt->execute();
        $stmt->close();

        // Ensure a downloadable recommendation_letter record exists
        $letterCheck = $connection->prepare(
            'SELECT recommendation_letter_id FROM recommendation_letters WHERE recommendation_request_id = ? LIMIT 1'
        );
        $letterCheck->bind_param('i', $requestId);
        $letterCheck->execute();
        $hasLetter = $letterCheck->get_result()->fetch_assoc();
        $letterCheck->close();

        if (!is_array($hasLetter)) {
            $uploadsDir = __DIR__ . '/../uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $storageKey = 'rec_letter_' . $requestId . '_' . time() . '.pdf';
            $filePath = $uploadsDir . '/' . $storageKey;

            $pdfContent = generate_test_recommendation_pdf_content($existing);

            file_put_contents($filePath, $pdfContent);
            $byteSize = strlen($pdfContent);
            $sha256 = hash('sha256', $pdfContent);

            $fileStmt = $connection->prepare(
                'INSERT INTO files (uploaded_by_user_id, storage_key, original_filename, mime_type, byte_size, checksum_sha256) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $origFilename = 'Recommendation_Letter_' . $requestId . '.pdf';
            $mimeType = 'application/pdf';
            $fileStmt->bind_param('isssis', $userId, $storageKey, $origFilename, $mimeType, $byteSize, $sha256);
            $fileStmt->execute();
            $fileId = $connection->insert_id;
            $fileStmt->close();

            $letterStmt = $connection->prepare(
                'INSERT INTO recommendation_letters (recommendation_request_id, issued_by, file_id, letter_title, issued_date) VALUES (?, ?, ?, ?, CURDATE())'
            );
            $letterTitle = 'Official Recommendation Letter (TEST)';
            $letterStmt->bind_param('iiis', $requestId, $managementProfileId, $fileId, $letterTitle);
            $letterStmt->execute();
            $letterStmt->close();
        }

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    if (isset($existing['graduate_user_id']) && (int) $existing['graduate_user_id'] > 0) {
        send_mugts_notification_email(
            (int) $existing['graduate_user_id'],
            'Recommendation Letter Ready (TEST)',
            'Recommendation Letter Ready (TEST)',
            'Your requested recommendation letter has been issued (TEST document) and is ready for download in your portal.',
            '/recommendations'
        );
    }

    $updated = fetch_management_recommendation_request($connection, $requestId);
    json_success('Recommendation request approved successfully.', $updated);
}

function reject_recommendation_request(int $requestId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    $managementProfileId = management_profile_id($connection, $userId);

    $connection->begin_transaction();
    try {
        $existing = fetch_management_recommendation_request($connection, $requestId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Recommendation request was not found.', [], 404);
        }

        $stmt = $connection->prepare(
            'UPDATE recommendation_requests SET request_status = \'Rejected\', processed_at = NOW(6), processed_by = ? WHERE recommendation_request_id = ?'
        );
        $stmt->bind_param('ii', $managementProfileId, $requestId);
        $stmt->execute();
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $updated = fetch_management_recommendation_request($connection, $requestId);
    json_success('Recommendation request rejected successfully.', $updated);
}

function upload_recommendation_letter(int $requestId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    $managementProfileId = management_profile_id($connection, $userId);

    $uploadedFile = $_FILES['letter'] ?? $_FILES['file'] ?? $_FILES['document'] ?? null;
    if ($uploadedFile === null || !is_array($uploadedFile) || ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        json_error('Please attach a valid recommendation letter file to upload.', [], 400);
    }

    $fileName = (string) $uploadedFile['name'];
    $tmpPath = (string) $uploadedFile['tmp_name'];
    $byteSize = (int) $uploadedFile['size'];
    $mimeType = (string) ($uploadedFile['type'] ?? 'application/pdf');

    if ($byteSize > 10 * 1024 * 1024) {
        json_error('File size exceeds the 10 MB maximum limit.', [], 400);
    }

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        json_error('Invalid file type. Only PDF and Word documents are permitted.', [], 400);
    }

    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $storageKey = 'rec_letter_' . $requestId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadsDir . '/' . $storageKey;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        json_error('Failed to save uploaded file on server.', [], 500);
    }

    $sha256 = hash_file('sha256', $targetPath);

    $connection->begin_transaction();
    try {
        $existing = fetch_management_recommendation_request($connection, $requestId);
        if ($existing === null) {
            rollback_transaction($connection);
            @unlink($targetPath);
            json_error('Recommendation request was not found.', [], 404);
        }

        $fileStmt = $connection->prepare(
            'INSERT INTO files (uploaded_by_user_id, storage_key, original_filename, mime_type, byte_size, checksum_sha256) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $fileStmt->bind_param('isssis', $userId, $storageKey, $fileName, $mimeType, $byteSize, $sha256);
        $fileStmt->execute();
        $fileId = $connection->insert_id;
        $fileStmt->close();

        $checkStmt = $connection->prepare(
            'SELECT recommendation_letter_id FROM recommendation_letters WHERE recommendation_request_id = ? LIMIT 1'
        );
        $checkStmt->bind_param('i', $requestId);
        $checkStmt->execute();
        $existingLetter = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (is_array($existingLetter) && isset($existingLetter['recommendation_letter_id'])) {
            $letterId = (int) $existingLetter['recommendation_letter_id'];
            $updateLetterStmt = $connection->prepare(
                'UPDATE recommendation_letters SET issued_by = ?, file_id = ?, letter_title = ?, issued_date = CURDATE() WHERE recommendation_letter_id = ?'
            );
            $updateLetterStmt->bind_param('iisi', $managementProfileId, $fileId, $fileName, $letterId);
            $updateLetterStmt->execute();
            $updateLetterStmt->close();
        } else {
            $insertLetterStmt = $connection->prepare(
                'INSERT INTO recommendation_letters (recommendation_request_id, issued_by, file_id, letter_title, issued_date) VALUES (?, ?, ?, ?, CURDATE())'
            );
            $insertLetterStmt->bind_param('iiis', $requestId, $managementProfileId, $fileId, $fileName);
            $insertLetterStmt->execute();
            $insertLetterStmt->close();
        }

        $reqStmt = $connection->prepare(
            'UPDATE recommendation_requests SET request_status = \'Approved\', processed_at = NOW(6), processed_by = ? WHERE recommendation_request_id = ?'
        );
        $reqStmt->bind_param('ii', $managementProfileId, $requestId);
        $reqStmt->execute();
        $reqStmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        @unlink($targetPath);
        throw $exception;
    }

    if (isset($existing['graduate_user_id']) && $existing['graduate_user_id'] > 0) {
        send_mugts_notification_email(
            (int) $existing['graduate_user_id'],
            'Recommendation Letter Ready',
            'Recommendation Letter Ready',
            'Your requested recommendation letter has been issued and is ready for download in your portal.',
            '/recommendations'
        );
    }

    $updated = fetch_management_recommendation_request($connection, $requestId);
    json_success('Recommendation letter uploaded and request approved successfully.', $updated);
}
