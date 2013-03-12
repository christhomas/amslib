/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_DDSlick.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	Amslib.loadCSS(amslib+"/util/bootstrap.datetimepicker/bootstrap-datetimepicker.min.css");
	Amslib.loadJS("bootstrap.datetimepicker",amslib+"/util/bootstrap.datetimepicker/bootstrap-datetimepicker.min.js");
};
