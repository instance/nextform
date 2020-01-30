/*
 * NextForm Support for Bootstrap4. Assumes jQuery
 */
"use strict";

class NextForm {

    constructor(form) {
        this.form = form;
        this.groupCollector();
    }

    checkGroup(group, checked) {
        if (this.groupList[group] === undefined) return;
        this.groupList[group].forEach(function (jqElement) {
            var type = jqElement.prop('type');
            if (type === 'checkbox' || type === 'radio') {
                jqElement.prop('checked', checked);
                jqElement.change();
            }
        });
    }

    check(element, checked) {
        if (element.type === 'checkbox' || element.type === 'radio') {
            element.checked = checked;
            $(element).change();
        }
    }

    disableContainer(name, disable) {
        var element = this.getContainer(name);
        if (element === undefined) return;
        element.prop('disabled', disable);
    }

    disableGroup(group, disable) {
        if (this.groupList[group] === undefined) return;
        this.groupList[group].forEach(function (jqElement) {
            jqElement.prop('disabled', disable);
        });
    }

    displayContainer(name, show) {
        var element = this.getContainer(name);
        if (element === undefined) return;
        element.toggle(show);
    }

    displayGroup(group, show) {
        if (this.groupList[group] === undefined) return;
        this.groupList[group].forEach(function (jqElement) {
            jqElement.toggle(show);
        });
    }

    getContainer(name) {
        var element = $('[name=' + name + ']', this.form);
        if (element === undefined) return undefined;
        var container = $('#' + element.attr('id') + Manager::CONTAINER_LABEL);
        return container;
    }

    /**
     * Get a list of JQuery objects for the elements in each group.
     *
     * @returns {Boolean}
     */
    groupCollector() {
        let rawGroups = [];
        this.form.find('*[data-nf-group]').each(function (i, element) {
            $(element).data('nf-group').forEach(function (group) {
                if (rawGroups[group] === undefined) {
                    rawGroups[group] = [];
                }
                rawGroups[group].push($(element));
            });
        });
        this.groupList = rawGroups;
        return true;
    }

}

