// Quick fix that will be replaced with own sidebar after move to webpack and react
function customizePolylangSidebar(settings, name) {
  if (name !== "polylang-sidebar") {
    return settings;
  }

  var Component = wp.element.Component;
  var el = wp.element.createElement;

  function CustomScript() {
    Component.call(this);
  }
  
  CustomScript.prototype = Object.create(Component.prototype);
  CustomScript.prototype.constructor = CustomScript;
  CustomScript.prototype.render = function(){
    return el(settings.render);
  };
  CustomScript.prototype.componentDidUpdate = function() {
    window.setTimeout(Supertext.Interface.injectOrderLinks, 10);
  };

  return lodash.assign({}, settings, {
    render: CustomScript
  });
}

wp.hooks.addFilter(
  "plugins.registerPlugin",
  "polylang-supertext/sidebar",
  customizePolylangSidebar
);
