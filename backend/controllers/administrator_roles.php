<?php

declare(strict_types=1);
function list_administrator_roles(): never { require_administrator(); $connection = database_connection(); $s = $connection->prepare('SELECT role_id, code, name, created_at FROM roles ORDER BY role_id'); $s->execute(); $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); json_success('Roles retrieved successfully.', $rows); }
function list_administrator_statuses(): never { require_administrator(); $connection = database_connection(); $s = $connection->prepare('SELECT account_status_id, code, name, is_active, created_at FROM account_statuses ORDER BY account_status_id'); $s->execute(); $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); json_success('Account statuses retrieved successfully.', $rows); }
