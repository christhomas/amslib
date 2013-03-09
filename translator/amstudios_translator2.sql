create table amstudios_translation (
	id int not null auto_increment,
	lang varchar(5),
	object_id int not null,
	name varchar(255),
	value text,
	
	primary key (id)
) Engine=InnoDB, CHARACTER SET UTF8;