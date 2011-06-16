create table amstudios_translator (
	id				int not null auto_increment,
	lang			varchar(5),
	expr			varchar(255),
	string		text,
	
	primary key (id)
) type=InnoDB, CHARACTER SET UTF8;