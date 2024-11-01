jQuery(function ($) {

    $(document).on("click", ".uspss-debug-details", function(e) {
        e.preventDefault();
        $(this).nextAll(".uspss-debug-inner").toggle();
    });

    const cp = new ClipboardJS(".uspss-debug-copy", {
        text: function(trigger) {
            return $(trigger).nextAll(".uspss-debug-inner").get(0).innerText.trim();
        }
    });
    cp.on('success', function(e) {

        const trig = e.trigger;

        if (!trig._uspssOrigText) {
            trig._uspssOrigText = trig.innerText;
        }

        trig.innerText = 'copied!';

        if (trig._uspsTimer) {
            clearTimeout(trig._uspsTimer);
            trig._uspsTimer = undefined;
        }
        trig._uspsTimer = setTimeout(function() {
            trig.innerText = trig._uspssOrigText;
        }, 3000);
    });
});
