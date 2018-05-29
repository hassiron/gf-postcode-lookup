/**
 * The JavaScript functionality bound to the GF Postcode Look-up field
 * 
 * @since 1.0.0
 * @package GF_Postcode_Lookup
 */

// packages
import PostcodeException from './Exceptions/PostcodeException';
import AjaxException from './Exceptions/PostcodeException';

const Notifier = require('sweetalert2');
const Postcode = require('postcode');

// constants
const PREFIX = 'gfpcl';
const AJAX_URL = `${window.location.origin}/wp-admin/admin-ajax.php`;
const $ = window.jQuery;

const fieldAssociations = {
    line_1: '1',
    line_2: '2',
    city: 'city',
    county: 'county',
    postcode: 'postcode'
};

/**
 * A jQuery selector wrapper, specific to plugin elements
 */
const dollar = selector => $(`${selector.substr(0, 1)}${PREFIX}-${selector.substr(1)}`);

/**
 * Document initialisation
 */
$(function () {
    const $trigger = dollar('#trigger-lookup');
    const $searchWrap = dollar('.initial');
    const $postcodeInput = $searchWrap.find($trigger.data('postcode-input'));
    const $resultDropdown = $searchWrap.find('.lookup-results');
    const $resultList = $resultDropdown.children('.result-list');
    const $addressFields = dollar('.address-fields');
    const $formToggle = $('#toggle-form-state');
    
    if ($trigger.length) {
        $trigger.on('click', function (event) {
            event.preventDefault();

            $searchWrap.addClass('loading');
            $resultList.empty();

            try {
                let userPostcode = validatePostcode($postcodeInput.val());
                let request = $.ajax({
                    url: AJAX_URL,
                    method: 'POST',
                    data: {
                        action: 'gf-postcode-lookup',
                        postcode: userPostcode
                    }
                });

                request.success(response => {
                    $searchWrap.removeClass('loading');
                    
                    if (response.status === 200 && response.data.length > 0) {
                        for (let i in response.data) {
                            let address = Object.filter(response.data[i], line => line.trim() !== '');
                            let subLines = [];
                            let $el = $('<li />', {
                                class: 'result',
                                html: `<p class="first-line">${address.line_1}</p>`
                            });

                            $el.data('postcode', userPostcode);

                            for (let key in address) {
                                let value = address[key];

                                $el.data(key, value);

                                if (key !== 'line_1') {
                                    subLines.push(`<span class="${key}">${value}</span>`);
                                }
                            }

                            if (subLines.length) {
                                $el.append(`<small class="sub-lines">${subLines.join(', ')}</small>`);
                            }

                            $el.appendTo($resultList);
                        }

                        $resultDropdown.show();
                    } else {
                        // @TODO we need proper handling for the API status
                        throw new PostcodeException("We couldn't find any addresses matching the given postcode");
                    }
                });

                request.fail((error, message) => {
                    throw new AjaxException(error);
                });
            } catch (e) {
                $searchWrap.removeClass('loading');

                Notifier(e.message);
            }
        });

        $(document).on('click', '.result-list .result', function () {
            let $result = $(this);
            let lines = $result.data();

            for (let line in lines) {
                if (fieldAssociations.hasOwnProperty(line)) {
                    let $input = $(`#${$addressFields.attr('id')}_${fieldAssociations[line]}`);

                    if ($input.length) {
                        $input.val(lines[line]);
                    }
                }
            }

            $resultDropdown.hide();
            $addressFields.stop().slideDown();
        });

        $postcodeInput.on('keyup keydown', function (event) {
            if (event.keyCode === 13) {
                event.stopPropagation();
                event.preventDefault();

                $trigger.trigger('click');
            }
        });

        $formToggle.on('click', function (event) {
            event.preventDefault();

            let $toggle = $(this);

            if ($toggle.hasClass('manual')) {
                $toggle.removeClass('manual').text($toggle.data('default'));
            } else {
                $toggle.addClass('manual').text($toggle.data('manual'));
            }

            $searchWrap.stop().slideToggle();
            $addressFields.stop().slideToggle();
        });
    }
});

/**
 * Response handler functions
 */
const handleSuccessfulLookup = data => {
    console.log(data);
};

/**
 * Postcode validation wrapper that throws an error if the postcode isn't valid
 */
const validatePostcode = postcode => {
    postcode = new Postcode(postcode);

    if (!postcode.valid()) {
        throw new PostcodeException('The postcode provided is not valid');
    }

    return postcode.normalise();
};

/**
 * Additional methods
 */
Object.filter = (obj, predicate) => Object.keys(obj).filter(key => predicate(obj[key])).reduce((res, key) => (res[key] = obj[key], res), {});
