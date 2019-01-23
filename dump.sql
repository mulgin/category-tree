-- Дамп структуры для таблица tree.category
CREATE TABLE IF NOT EXISTS `category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `lvl` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;