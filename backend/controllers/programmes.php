<?php

declare(strict_types=1);

function list_programmes(): never
{
    $connection = database_connection();
    $statement = $connection->prepare(
        'SELECT p.programme_id, p.name AS programme_name, p.code AS programme_code,
                d.name AS department_name, f.name AS faculty_name,
                el.name AS education_level_name
         FROM programmes AS p
         INNER JOIN departments AS d ON d.department_id = p.department_id
         INNER JOIN faculties AS f ON f.faculty_id = d.faculty_id
         INNER JOIN education_levels AS el ON el.education_level_id = p.education_level_id
         WHERE p.is_active = 1
           AND d.is_active = 1
           AND f.is_active = 1
           AND el.is_active = 1
         ORDER BY f.name, d.name, el.sort_order, p.name'
    );
    $statement->execute();
    $programmes = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    json_success('Programmes retrieved successfully.', $programmes);
}

function list_academic_metadata(): never
{
    $connection = database_connection();
    
    $stmt1 = $connection->prepare(
        'SELECT p.programme_id, p.name AS programme_name, p.code AS programme_code,
                d.department_id, d.name AS department_name,
                f.faculty_id, f.name AS faculty_name,
                el.education_level_id, el.name AS education_level_name
         FROM programmes AS p
         INNER JOIN departments AS d ON d.department_id = p.department_id
         INNER JOIN faculties AS f ON f.faculty_id = d.faculty_id
         INNER JOIN education_levels AS el ON el.education_level_id = p.education_level_id
         WHERE p.is_active = 1
         ORDER BY f.name, d.name, p.name'
    );
    $stmt1->execute();
    $programmes = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt1->close();

    $stmt2 = $connection->prepare('SELECT department_id, faculty_id, name, code FROM departments WHERE is_active = 1 ORDER BY name');
    $stmt2->execute();
    $departments = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    $stmt3 = $connection->prepare('SELECT faculty_id, name, code FROM faculties WHERE is_active = 1 ORDER BY name');
    $stmt3->execute();
    $faculties = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt3->close();

    $stmt4 = $connection->prepare('SELECT graduation_status_id, code, name FROM graduation_statuses WHERE is_active = 1 ORDER BY name');
    $stmt4->execute();
    $graduationStatuses = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt4->close();

    json_success('Academic metadata retrieved successfully.', [
        'programmes' => $programmes,
        'departments' => $departments,
        'faculties' => $faculties,
        'graduation_statuses' => $graduationStatuses,
    ]);
}

