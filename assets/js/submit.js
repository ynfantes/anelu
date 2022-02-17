

$(document).ready(function()  {

    submitForm = (form) => {

        debugger;
        // Set up an event listener for the contact form.
        $(form).submit(function(e) {
            
            e.preventDefault();
            const formData = $(form).serialize();
            $.ajax({
                type: 'POST',
                url: $(form).attr('action'),
                data: formData
            })
            .done(function(response) {
                return response        
            })
            .fail(function(data) {
                return data.responseText;
                
            });
        });

        
    }

});