CREATE TABLE IF NOT EXISTS `tbl` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL ,
  `name` varchar(255) NOT NULL);

CREATE TABLE IF NOT EXISTS `category` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `name` varchar(50) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `image` (
  `id`  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `name` varchar(50) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `product` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `image_id` int(11) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `product_has_category` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`)
);

INSERT INTO `category` (`id`, `name`) VALUES
  (1, 'M4 fasteners'),
  (2, 'M6 fasteners'),
  (3, 'M8 fasteners'),
  (4, 'Nuts'),
  (5, 'Bolts'),
  (6, 'Allen fasteners');

INSERT INTO `image` (`id`, `name`) VALUES
  (1, 'M4 nut'),
  (2, 'M6 nut'),
  (3, 'M8 nut'),
  (4, 'M4 bolt generic'),
  (5, 'M6 bolt generic'),
  (6, 'M8 bolt generic');

INSERT INTO `product` (`id`, `name`, `image_id`) VALUES
  (1, 'M6x20, hex', 5),
  (2, 'M6x20, allen', 5),
  (3, 'M4x60, philips countersunk', 4),
  (4, 'M4x60, allen', NULL),
  (5, 'M4x55, hex', NULL),
  (6, 'M4 nut', 1),
  (7, 'M6 nut', 2),
  (8, 'M8 nut', 3),
  (9, 'M8x40, allen', 6),
  (10, 'M8x40, hex', 6);

INSERT INTO `product_has_category` (`product_id`, `category_id`) VALUES
  (1, 5),
  (2, 5),
  (3, 5),
  (4, 5),
  (4, 1),
  (4, 6),
  (5, 5),
  (6, 4),
  (7, 4),
  (8, 4),
  (9, 5),
  (10, 5);