SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- stock_manager_rule
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stock_manager_rule`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `position` INTEGER NOT NULL,
    `source_status_id` INTEGER,
    `target_status_id` INTEGER,
    `operation` VARCHAR(16) NOT NULL,
    `payment_restricted` TINYINT(1) DEFAULT 0 NOT NULL,
    `payment_module_id` INTEGER,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `idx_stock_manager_rule_transition` (`source_status_id`, `target_status_id`),
    INDEX `fi_stock_manager_rule_target_status` (`target_status_id`),
    INDEX `fi_stock_manager_rule_payment_module` (`payment_module_id`),
    CONSTRAINT `fk_stock_manager_rule_source_status`
        FOREIGN KEY (`source_status_id`)
        REFERENCES `order_status` (`id`)
        ON UPDATE RESTRICT
        ON DELETE SET NULL,
    CONSTRAINT `fk_stock_manager_rule_target_status`
        FOREIGN KEY (`target_status_id`)
        REFERENCES `order_status` (`id`)
        ON UPDATE RESTRICT
        ON DELETE SET NULL,
    CONSTRAINT `fk_stock_manager_rule_payment_module`
        FOREIGN KEY (`payment_module_id`)
        REFERENCES `module` (`id`)
        ON UPDATE RESTRICT
        ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET='utf8mb4' COLLATE='utf8mb4_general_ci' ROW_FORMAT=DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
