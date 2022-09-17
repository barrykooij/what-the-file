window.addEventListener('load', (event) => {

  if(wtf_templates === undefined) {
    return;
  }

  // check if there are templates
  if (wtf_templates.length > 0) {
    // the parent selector
    var parent = document.querySelector("#wp-admin-bar-wtf-bar-template-parts #wp-admin-bar-wtf-bar-template-parts-default");

    // clear the loading
    parent.replaceChildren();

    // add new children
    for(let i = 0; i < wtf_templates.length; i++) {
      let listItem = document.createElement("li");
      listItem.id = "wp-admin-bar-wtf-bar-template-part-template-parts/"+i;
      let templateName = document.createElement("a");
      templateName.className="ab-item";
      templateName.href = wtf_templates[i].edit_url;
      templateName.textContent = wtf_templates[i].file;

      listItem.appendChild(templateName);
      parent.appendChild(listItem);
    }
  }else {
    // we have no template parts, remove the menu item for this page
    document.querySelector("#wp-admin-bar-wtf-bar-template-parts").remove();
  }
});
