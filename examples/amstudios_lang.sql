create table amstudios_lang (
	id_lang				int not null auto_increment,
	code					varchar(5) comment "E.g: es_ES, en_GB, en_US",
	scode				varchar(2) comment "E.g: es, en, us, ca",
	
	primary key (id_lang)
) Engine=InnoDB, CHARACTER SET UTF8;

insert into amstudios_lang set code="en_GB",scode="en";
insert into amstudios_lang set code="es_ES",scode="es";
insert into amstudios_lang set code="ca_CA",scode="ca";