<?php
function add_batch_log($pdo, $batch_id, $operator_name, $action){
    $stmt = $pdo->prepare("INSERT INTO reimbursement_batch_logs (batch_id, operator_name, action) VALUES (?,?,?)");
    $stmt->execute([$batch_id, $operator_name, $action]);
}
?>
