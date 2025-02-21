CREATE TABLE IF NOT EXISTS `[PREFIX]mp_note_attachment` (
    `id_mp_note_attachment` int(11) NOT NULL AUTO_INCREMENT,
    `id_mp_note` int(11) NOT NULL,
    `id_customer` int(11) NOT NULL,
    `id_order` int(11) NOT NULL,
    `type` int(11) NOT NULL,
    `filename` varchar(255) NOT NULL,
    `filetitle` varchar(255) NOT NULL,
    `file_ext` varchar(255) NOT NULL,
    `deleted` tinyint(4) NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_mp_note_attachment`),
    KEY `id_mp_note_order` (`id_mp_note`)
) ENGINE = InnoDB;