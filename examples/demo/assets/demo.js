(function () {
    function field(name) {
        return document.querySelector('[data-field-name="' + name + '"]');
    }

    function control(name) {
        return document.querySelector('[name="params[' + name + ']"]');
    }

    function prettyJson(value) {
        return JSON.stringify(value, null, 2);
    }

    function setupCustomerMode() {
        var mode = control("customerMode");
        if (!mode) {
            return;
        }
        var customerIdField = field("customerId");
        var customerField = field("customer");

        function sync() {
            var useCustomerId = mode.value === "customerId";
            if (customerIdField) {
                customerIdField.classList.toggle("is-hidden", !useCustomerId);
            }
            if (customerField) {
                customerField.classList.toggle("is-hidden", useCustomerId);
            }
        }

        mode.addEventListener("change", sync);
        sync();
    }

    function setupPaymentMethodData() {
        var method = control("paymentMethod");
        var data = control("paymentMethodData");
        var examples = window.paymentMethodDataExamples || {};
        if (!method || !data) {
            return;
        }

        method.addEventListener("change", function () {
            var example = examples[method.value];
            if (example) {
                data.value = prettyJson(example);
            }
        });
    }

    setupCustomerMode();
    setupPaymentMethodData();
})();
