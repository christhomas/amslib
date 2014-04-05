create table amstudios_translation (
	id_translation	int not null auto_increment,
	id_lang				int not null						comment "The foreign key of the language in the database",
	id_object			int	 default 0						comment "The optional id or code which is used to make difference between similar keys",
	name				varchar(255) not null		comment "The name of this translation",
	value				longtext not null				comment "The value of this translation key for the specified language",
	id_parent			int										comment "The previous version of this translation before it was modified",
	
	time_create		timestamp						comment "The mysql timestamp when this translation was created",
	time_edit			timestamp						comment "The mysql timestamp when this translation was edited",
	
	primary key (id_translation),
	
	index (id_lang),
	foreign key (id_lang) references amstudios_lang (id_lang),
	
	index (id_parent),
	foreign key (id_parent) references amstudios_translation (id_translation)
) Engine=InnoDB, CHARACTER SET UTF8;