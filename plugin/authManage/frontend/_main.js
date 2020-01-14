/**
 * @const lx.Plugin Plugin
 * */

/*
Косова Анастасия Владимировна
17,12 - 2726,32
19,12 - 2726,32
*/




class Test1 extends lx.BindableModel {
	#lx:schema
		name, pk;

	constructor(name, pk) {
		super();

		this.name = name;
		this.pk = pk;
	}
}

var m = new Test1('wewe', 123);
// console.log(m);



