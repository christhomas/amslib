Effect.Appear = function(element)
{
	element = $(element);
	var options = Object.extend({
		from: (element.getStyle('display') == 'none' ? 0.0 : element.getOpacity() || 0.0),
		to:   1.0,
		css: { display: "block" },
		//	force Safari to render floated elements properly
		afterFinishInternal: function(effect) {
			effect.element.forceRerendering();
		},
		beforeSetup: function(effect) {
			effect.element.setOpacity(effect.options.from).show(effect.options.css.display);
		}
	}, arguments[1] || { });

	return new Effect.Opacity(element,options);
}

$w('appear').each(function(effect)
{
	Effect.Methods[effect] = function(element, options){
		element = $(element);
		Effect[effect.charAt(0).toUpperCase() + effect.substring(1)](element, options);
		return element;
	};
});

Element.addMethods(Effect.Methods);