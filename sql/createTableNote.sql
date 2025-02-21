CREATE TABLE IF NOT EXISTS `[PREFIX]mp_note` (
    `id_mp_note` int(11) NOT NULL AUTO_INCREMENT,
    `type` int(11) NOT NULL,
    `id_customer` int(11) NOT NULL,
    `id_employee` int(11) NOT NULL,
    `id_order` int(11) NOT NULL,
    `note` text NOT NULL,
    `alert` int(11) DEFAULT 1,
    `printable` tinyint(1) NOT NULL,
    `chat` tinyint(1) NOT NULL,
    `deleted` tinyint(1) NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime DEFAULT NULL,
    PRIMARY KEY (`id_mp_note`),
    KEY `id_order` (`id_order`),
    KEY `id_customer` (`id_customer`),
    KEY `id_employee` (`id_employee`)
) ENGINE = InnoDB;