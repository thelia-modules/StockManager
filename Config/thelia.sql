
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- stock_operation
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `stock_operation`;

CREATE TABLE `stock_operation`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `operation` VARCHAR(25),
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- stock_operation_source_status
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `stock_operation_source_status`;

CREATE TABLE `stock_operation_source_status`
(
    `stock_operation_id` INTEGER NOT NULL,
    `source_status_id` INTEGER NOT NULL,
    PRIMARY KEY (`stock_operation_id`,`source_status_id`),
    INDEX `fi_stock_operation_source_status_os` (`source_status_id`),
    CONSTRAINT `fk_stock_operation_source_status_so`
        FOREIGN KEY (`stock_operation_id`)
        REFERENCES `stock_operation` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_stock_operation_source_status_os`
        FOREIGN KEY (`source_status_id`)
        REFERENCES `order_status` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- stock_operation_target_status
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `stock_operation_target_status`;

CREATE TABLE `stock_operation_target_status`
(
    `stock_operation_id` INTEGER NOT NULL,
    `target_status_id` INTEGER NOT NULL,
    PRIMARY KEY (`stock_operation_id`,`target_status_id`),
    INDEX `fi_stock_operation_target_status_os` (`target_status_id`),
    CONSTRAINT `fk_stock_operation_target_status_so`
        FOREIGN KEY (`stock_operation_id`)
        REFERENCES `stock_operation` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_stock_operation_target_status_os`
        FOREIGN KEY (`target_status_id`)
        REFERENCES `order_status` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- stock_operation_payment_module
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `stock_operation_payment_module`;

CREATE TABLE `stock_operation_payment_module`
(
    `stock_operation_id` INTEGER NOT NULL,
    `payment_module_id` INTEGER NOT NULL,
    PRIMARY KEY (`stock_operation_id`,`payment_module_id`),
    INDEX `fi_stock_operation_payment_module_m` (`payment_module_id`),
    CONSTRAINT `fk_stock_operation_payment_module_so`
        FOREIGN KEY (`stock_operation_id`)
        REFERENCES `stock_operation` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_stock_operation_payment_module_m`
        FOREIGN KEY (`payment_module_id`)
        REFERENCES `module` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- stock_operation_delivery_module
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `stock_operation_delivery_module`;

CREATE TABLE `stock_operation_delivery_module`
(
    `stock_operation_id` INTEGER NOT NULL,
    `delivery_module_id` INTEGER NOT NULL,
    PRIMARY KEY (`stock_operation_id`,`delivery_module_id`),
    INDEX `fi_stock_operation_delivery_module_m` (`delivery_module_id`),
    CONSTRAINT `fk_stock_operation_delivery_module_so`
        FOREIGN KEY (`stock_operation_id`)
        REFERENCES `stock_operation` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_stock_operation_delivery_module_m`
        FOREIGN KEY (`delivery_module_id`)
        REFERENCES `module` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
