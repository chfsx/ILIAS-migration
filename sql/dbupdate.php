<#1>
CREATE TABLE glossary_term
(
	id			INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	glo_id		INT NOT NULL,
	term		VARCHAR(200),
	language	CHAR(2),
	INDEX glo_id (glo_id)
);

<#2>
CREATE TABLE glossary_definition
(
	id			INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	term_id		INT NOT NULL,
	page_id		INT NOT NULL
);

<#3>
CREATE TABLE desktop_item
(
	user_id		INT NOT NULL,
	item_id		INT NOT NULL,
	type		CHAR(4) NOT NULL,
	INDEX user_id (user_id)
);

<#4>
UPDATE object_data SET title = 'ILIAS' WHERE title = 'ILIAS open source';

<#5>
REPLACE INTO lm_data (obj_id, title, type, lm_id) VALUES (1, 'dummy', 'du', 0);
<#6>
UPDATE role_data SET allow_register = 1 WHERE role_id = 5; 
