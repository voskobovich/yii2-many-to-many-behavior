BEGIN TRANSACTION;
CREATE TABLE "review" (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`book_id`	INTEGER,
	`comment`	VARCHAR(150) NOT NULL,
	`rating`	INTEGER NOT NULL
);
INSERT INTO `review` VALUES (1,3,'Старая книга, не потерявшая актуальность.',5);
INSERT INTO `review` VALUES (2,3,'Одобряю!',5);
INSERT INTO `review` VALUES (3,3,'Неплохо.',4);
INSERT INTO `review` VALUES (4,5,'Хлам!',2);
CREATE TABLE "book_has_author" (
	`book_id`	INTEGER NOT NULL,
	`author_id`	INTEGER NOT NULL
);
INSERT INTO `book_has_author` VALUES (1,1);
INSERT INTO `book_has_author` VALUES (1,2);
INSERT INTO `book_has_author` VALUES (2,1);
INSERT INTO `book_has_author` VALUES (2,3);
INSERT INTO `book_has_author` VALUES (3,4);
INSERT INTO `book_has_author` VALUES (4,5);
INSERT INTO `book_has_author` VALUES (4,6);
INSERT INTO `book_has_author` VALUES (5,9);
CREATE TABLE "book" (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`name`	VARCHAR(150) NOT NULL,
	`year`	INTEGER NOT NULL
);
INSERT INTO `book` VALUES (1,'Основы агрономии и ботаники.',2004);
INSERT INTO `book` VALUES (2,'Ботаника: учеб для с/вузов.',2005);
INSERT INTO `book` VALUES (3,'Краткий словарь ботанических терминов.',1964);
INSERT INTO `book` VALUES (4,'Ботаника с основами геоботаники.',1979);
INSERT INTO `book` VALUES (5,'Ботаника. Систематика высших или наземных растений.',2004);
CREATE TABLE "author" (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`name`	VARCHAR(150) NOT NULL
);
INSERT INTO `author` VALUES (1,'Андреев Н.Г.');
INSERT INTO `author` VALUES (2,'Андреев Л.Н.');
INSERT INTO `author` VALUES (3,'Родман Л.С.');
INSERT INTO `author` VALUES (4,'Викторов Д.П.');
INSERT INTO `author` VALUES (5,'Суворов В.В.');
INSERT INTO `author` VALUES (6,'Воронов И.Н.');
INSERT INTO `author` VALUES (7,'Еленевский А.Г.');
INSERT INTO `author` VALUES (8,'Соловьева М.П.');
INSERT INTO `author` VALUES (9,'Тихомиров В.Н.');
CREATE TABLE "settings" (
	`key`	VARCHAR(150) NOT NULL,
	`value`	VARCHAR(150) NOT NULL
);
INSERT INTO `settings` VALUES ('default_review','17');
COMMIT;