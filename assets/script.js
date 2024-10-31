jQuery(document).ready(function () {
    const $ = jQuery;
    if ($('#css-textarea').length) {
        wp.codeEditor.initialize($('#css-textarea'), cm_settings);
    }

    $('.parcello__select').each(function () {
        console.log($this);
        var $this = $(this), numberOfOptions = $(this).children('option').length;

        $this.addClass('select-hidden');
        $this.wrap('<div class="select"></div>');
        $this.after('<div class="select-styled"></div>');

        var $styledSelect = $this.next('div.select-styled');
        $styledSelect.append(`<span>${$this.find(':selected').text() || $this.children('option').eq(0).text()}</span>`);

        var $list = $('<ul />', {
            'class': 'select-options'
        }).insertAfter($styledSelect);

        for (var i = 0; i < numberOfOptions; i++) {
            $('<li />', {
                text: $this.children('option').eq(i).text(),
                rel: $this.children('option').eq(i).val()
            }).appendTo($list);
            if ($this.children('option').eq(i).is(':selected')) {
                $('li[rel="' + $this.children('option').eq(i).val() + '"]').addClass('is-selected')
            }
        }

        var $listItems = $list.children('li');

        $styledSelect.click(function (e) {
            e.stopPropagation();
            $('div.select-styled.active').not(this).each(function () {
                $(this).removeClass('active').next('ul.select-options').hide();
            });
            $(this).toggleClass('active').next('ul.select-options').toggle();
        });

        $listItems.click(function (e) {
            e.stopPropagation();
            $styledSelect.find('span').text($(this).text()).removeClass('active');
            $this.val($(this).attr('rel'));
            $list.hide();
            //console.log($this.val());
            $this.trigger('change');
        });

        $(document).click(function () {
            $styledSelect.removeClass('active');
            $list.hide();
        });
    });

    $('[data-trigger-submit]').change(function (event) {
        console.log('changing')
        $(event.target).closest('form').trigger('submit');
    })

    $('[data-xhr]').submit(function (event) {
        console.log('submitting')
        event.preventDefault();

        var ajax_form_data = $(event.target).serialize();

        console.log(ajax_form_data);

        ajax_form_data = ajax_form_data + '&ajaxrequest=true&submit=Submit+Form';

        $('#parcello_form_loader').removeClass('hidden');
        $(" #parcello_form_feedback-success ").addClass('hidden');
        $(" #parcello_form_feedback-failure ").addClass('hidden');
        console.log('removed Hidden');

        $.ajax({
            url: "/wp-admin/admin-ajax.php",
            type: 'post',
            data: ajax_form_data
        })
            .done(function () {
                $("#parcello_form_feedback-success").removeClass('hidden');
            })
            .fail(function () {
                $("#parcello_form_feedback-failure").removeClass('hidden');
            })
            .always(function () {
                $('#parcello_form_loader').addClass('hidden');
                event.target.reset();
            });
    });

})
