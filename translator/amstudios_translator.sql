create table amstudios_translator (
	id		int not null auto_increment,
	lang	varchar(5),
	k		varchar(255),
	v		text,
	
	primary key (id)
) type=InnoDB, CHARACTER SET UTF8;