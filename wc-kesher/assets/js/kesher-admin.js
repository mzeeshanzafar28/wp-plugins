jQuery(document).ready(function ($) {
    $('#woocommerce_kesher_installments').parent('label').after('<button id="rule-initiator" style="display: block;">+ Add Rule</button>');

    if ($('#woocommerce_kesher_installments').is(':checked')) {
        $('#rule-initiator').show();
        $('.rule-wrapper').show();
        renderRulesHtml();
        window.ruleexist = true;
    }
    else if (!$('#woocommerce_kesher_installments').is(':checked')) {
        $('#rule-initiator').hide();
        $('.rule-wrapper').hide();
    }
    function renderRulesHtml() {
        let rules = $('#woocommerce_kesher_ins_rules').val();
        rules = JSON.parse(rules);
        rules.reverse();
        rules.forEach(function (rule) {
            let ruleHTML = `
            <div class="rule-wrapper">
                <label>From</label>
                <input type="number" min="1" class="rule-from" value="${rule.from}">
                <label>To</label>
                <input type="number" min="1" class="rule-to" value="${rule.to}">
                => 
                <label>Number of Installments</label>
                <input type="number" min="1" class="rule-installments" value="${rule.installments}">
                <button class="rule-destroyer">Remove</button>
            </div>`;
            $('#rule-initiator').after(ruleHTML);
        });
    }

    function updateRuleData() {
        var ruleData = [];
        $('.rule-wrapper').each(function () {
            let $wrapper = $(this);
            let from = $wrapper.find('.rule-from').val();
            let to = $wrapper.find('.rule-to').val();
            let ins = $wrapper.find('.rule-installments').val();
            let data = {
                from: from,
                to: to,
                installments: ins
            };
            ruleData.push(data);
        });
        var jsonData = JSON.stringify(ruleData);
        $('#woocommerce_kesher_ins_rules').val(jsonData);
    }

    $('#woocommerce_kesher_installments').on('change', function () {
        if ($(this).is(':checked')) {
            $('#rule-initiator').show();
            $('.rule-wrapper').show();
            if (!window.ruleexist) {
                renderRulesHtml();
                window.ruleexist = true;
            }
        }
        else {
            $("#rule-initiator").hide();
            $('.rule-wrapper').hide();
            updateRuleData();
        }
    });

    $(document).on('click', '#rule-initiator', function (e) {
        e.preventDefault();
        if ($('.rule-wrapper').length < 1) {
            $(this).after('<div class="rule-wrapper"> <label> From </label> <input type="number" min="1" class="rule-from"> <label>To </label> <input type="number" min="1" class="rule-to"> => <label> Number of Installments</label> <input type="number" min="1" class="rule-installments"> <button class="rule-destroyer">Remove</button> </div>');
        } else {
            $(this).siblings('.rule-wrapper').last().after('<div class="rule-wrapper"> <label> From </label> <input type="number" min="1" class="rule-from"> <label>To </label> <input type="number" min="1" class="rule-to"> => <label> Number of Installments</label> <input type="number" min="1" class="rule-installments"> <button class="rule-destroyer">Remove</button> </div>');
        }
    });

    $(document).on('click', '.rule-destroyer', function () {
        $(this).parent('.rule-wrapper').remove();
        updateRuleData();
    });

    $(document).on('input', '.rule-from, .rule-to, .rule-installments', function () {
        updateRuleData();
    });

});
