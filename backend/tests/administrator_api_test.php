<?php

declare(strict_types=1);

/**
 * Administrator API smoke test.
 * Required environment: ADMIN_API_BASE_URL, ADMIN_TOKEN, GRADUATE_TOKEN, MANAGEMENT_TOKEN.
 * Set ADMIN_TEST_ALLOW_MUTATIONS=1 only against a disposable database to exercise write routes.
 */
function request(string $method, string $path, ?string $token = null, ?array $payload = null): array {
    $base = rtrim((string) getenv('ADMIN_API_BASE_URL'), '/');
    $headers = ['Content-Type: application/json'];
    if ($token !== null) $headers[] = 'Authorization: Bearer ' . $token;
    $options = ['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'ignore_errors' => true]];
    if ($payload !== null) $options['http']['content'] = json_encode($payload, JSON_THROW_ON_ERROR);
    $body = file_get_contents($base . $path, false, stream_context_create($options));
    $status = 0; foreach ($http_response_header ?? [] as $line) if (preg_match('#HTTP/\\S+ (\\d+)#', $line, $m)) { $status = (int) $m[1]; break; }
    return [$status, json_decode((string) $body, true)];
}
function expect(int $actual, int $expected, string $label): void { if ($actual !== $expected) throw new RuntimeException("$label: expected $expected, got $actual"); echo "PASS $label\n"; }
$base = getenv('ADMIN_API_BASE_URL'); $admin = getenv('ADMIN_TOKEN');
if (!$base || !$admin) throw new RuntimeException('Set ADMIN_API_BASE_URL and ADMIN_TOKEN before running this script.');
expect(request('GET', '/administrator/dashboard')[0], 401, 'anonymous request is rejected');
if ($graduate = getenv('GRADUATE_TOKEN')) expect(request('GET', '/administrator/dashboard', $graduate)[0], 403, 'graduate request is rejected');
if ($management = getenv('MANAGEMENT_TOKEN')) expect(request('GET', '/administrator/dashboard', $management)[0], 403, 'management request is rejected');
expect(request('GET', '/administrator/dashboard', $admin)[0], 200, 'administrator dashboard');
foreach (['/administrator/users', '/administrator/roles', '/administrator/statuses', '/administrator/faculties', '/administrator/departments', '/administrator/programmes', '/administrator/settings'] as $route) expect(request('GET', $route, $admin)[0], 200, "administrator GET $route");
if (getenv('ADMIN_TEST_ALLOW_MUTATIONS') !== '1') { echo "Write tests skipped (set ADMIN_TEST_ALLOW_MUTATIONS=1 on a disposable database).\n"; exit(0); }
$tag = 'Admin API Test ' . bin2hex(random_bytes(4));
[$status, $faculty] = request('POST', '/administrator/faculties', $admin, ['name' => $tag, 'code' => 'T' . random_int(10000, 99999)]); expect($status, 201, 'create faculty');
$facultyId = (int) $faculty['data']['faculty_id'];
[$status, $department] = request('POST', '/administrator/departments', $admin, ['name' => $tag, 'faculty_id' => $facultyId]); expect($status, 201, 'create department');
expect(request('DELETE', '/administrator/faculties/' . $facultyId, $admin)[0], 409, 'linked faculty deletion is rejected');
expect(request('DELETE', '/administrator/departments/' . $department['data']['department_id'], $admin)[0], 200, 'delete test department');
expect(request('DELETE', '/administrator/faculties/' . $facultyId, $admin)[0], 200, 'delete test faculty');
echo "Mutation smoke tests completed. Test the last-administrator guard only with a dedicated isolated fixture.\n";