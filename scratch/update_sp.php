<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Drop the existing procedure
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_assign_queue_ticket");
    
    // Recreate the procedure with the new guard
    $sql = "
    CREATE PROCEDURE sp_assign_queue_ticket(
        IN p_patient_id INT,
        IN p_service_id INT,
        IN p_assigned_by INT,
        OUT p_ticket_string VARCHAR(20),
        OUT p_queue_number INT
    )
    BEGIN
        DECLARE v_prefix VARCHAR(10);
        DECLARE v_next_num INT;
        DECLARE v_today DATE;
        
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Failed to assign queue ticket.';
        END;

        SET v_today = CURDATE();

        START TRANSACTION;

        -- Verify patient exists and is active
        IF NOT EXISTS (SELECT 1 FROM patients WHERE patient_id = p_patient_id AND is_archived = 0) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient record not found or archived.';
        END IF;

        -- Idempotency Guard: Prevent duplicate active queue tickets for the same patient today
        IF EXISTS (
            SELECT 1 FROM queue 
            WHERE patient_id = p_patient_id 
              AND queue_date = v_today 
              AND status IN ('Waiting', 'Serving')
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient already has an active queue ticket today.';
        END IF;

        -- Fetch service type prefix
        SELECT COALESCE(prefix, 'Q') INTO v_prefix 
        FROM service_types 
        WHERE service_id = p_service_id AND is_active = 1;
        
        IF v_prefix IS NULL THEN
            SET v_prefix = 'Q';
        END IF;

        -- Calculate next sequential ticket number under row lock (concurrency-safe)
        SELECT COALESCE(MAX(queue_number), 0) + 1 INTO v_next_num
        FROM queue
        WHERE queue_date = v_today
        FOR UPDATE;

        -- Insert queue ticket
        INSERT INTO queue (patient_id, service_id, queue_date, queue_number, status, assigned_by)
        VALUES (p_patient_id, p_service_id, v_today, v_next_num, 'Waiting', p_assigned_by);

        -- Format outputs
        SET p_queue_number = v_next_num;
        SET p_ticket_string = CONCAT(v_prefix, '-', LPAD(v_next_num, 3, '0'));

        COMMIT;
    END
    ";
    
    $pdo->exec($sql);
    echo "Stored procedure sp_assign_queue_ticket updated successfully.\n";
} catch (Exception $e) {
    echo "Error updating stored procedure: " . $e->getMessage() . "\n";
}
