define(['jquery'], function($) {

    var overrides = {};

    function overrideSubswitches(el, on) {
        var id = el.attr('id');
        if (overrides[id] !== undefined) {
            for (var i = 0; i < overrides[id].length; i++) {
                var o = overrides[id][i];
                if (on) {
                    o.addClass('overridden');
                } else {
                    o.removeClass('overridden');
                }
                overrideSubswitches(o, on);
            }
        }
    }

    return {
        init: function(o) {

            o = $(o);

            var override = o.data('override');
            if (override !== undefined) {
                if (overrides[override] === undefined) {
                    overrides[override] = [];
                }
                overrides[override].push(o);
            }

            o.html(
'<div class="toggle">' +
'<svg class="loading-indicator" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 1 1">' +
'    <g fill="black">' +
'       <rect x="0.45" y="0" width="0.1" height="0.37" rx="0.05" ry="0.05" />' +
'       <rect x="0.45" y="0" width="0.1" height="0.37" rx="0.05" ry="0.05" ' +
'           transform="rotate(-45 0.5 0.5)"  style="opacity: 1.0"/>' +
'       <rect x="0.45" y="0" width="0.1" height="0.37" rx="0.05" ry="0.05" ' +
'           transform="rotate(-90 0.5 0.5)"  style="opacity: 0.9"/>' +
'       <rect x="0.45" y="0" width="0.1" height="0.37" rx="0.05" ry="0.05" ' +
'           transform="rotate(-135 0.5 0.5)" style="opacity: 0.8" />' +
'       <rect x="0.45" y="0" width="0.1" height="0.37" rx="0.05" ry="0.05" ' +
'           transform="rotate(-180 0.5 0.5)" style="opacity: 0.7" />' +
'       <rect x="0.45" y="0" width="0.1" height="0.37" rx="0.05" ry="0.05" ' +
'           transform="rotate(-225 0.5 0.5)" style="opacity: 0.6" />' +
'       <rect x="0.45" y="0" width="0.1" height="0.37" rx="0.05" ry="0.05" ' +
'           transform="rotate(-270 0.5 0.5)" style="opacity: 0.5" />' +
'       <rect x="0.45" y="0" width="0.1" height="0.37" rx="0.05" ry="0.05" ' +
'           transform="rotate(-315 0.5 0.5)" style="opacity: 0.4" />' +
'       <animateTransform attributeName="transform" attributeType="XML" ' + 
'           type="rotate" from="0 0.5 0.5" to="360 0.5 0.5" dur="1.5s" repeatCount="indefinite"/>' +
'    </g>' +
'</svg>' +
'</div>');

            o.attr({
                'aria-role' : 'checkbox',
                'aria-checked': o.hasClass('checked') ? 'true' : 'false'
            });

            o.on('click', function() {

                o.toggleClass('checked');
                var checked = o.hasClass('checked');

                if (checked) {
                    o.attr('aria-checked', 'true');
                } else {
                    o.attr('aria-checked', 'false');
                }

                overrideSubswitches(o, checked);

                o.trigger('changed');

            });

            o.on('showLoading', function() {
                o.find('.loading-indicator').show();
            });

            o.on('hideLoading', function() {
                o.find('.loading-indicator').hide();
            });
        }
    };

});