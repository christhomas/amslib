create table amstudios_translation (
	id_translation	int not null auto_increment,
	id_lang				int not null			comment "The foreign key of the language in the database",
	id_object			int							comment "The optional id or code which is used to make difference between similar keys",
	name				varchar(255)		comment "The name of this translation",
	value				longtext				comment "The value of this translation key for the specified language",
	
	primary key (id_translation),
	
	index (id_lang),
	foreign key (id_lang) references amstudios_lang (id_lang)
) Engine=InnoDB, CHARACTER SET UTF8;