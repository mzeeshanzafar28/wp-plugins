jQuery(document).ready(function($) {
    $('#search-bar').on('input', function() {
        var searchTerm = $(this).val();
        if (searchTerm.trim().length >= 3) {
            $.ajax({
                url: searchman_ajax_object.ajax_url,
                type: 'GET',
                data: {
                    action: 'search_products',
                    search_term: searchTerm,
                },
                success: function(response) {
                    var resultsContainer = $('#search-results');
                    resultsContainer.empty();
                    if (response.success) {
                        var products = response.data;
                        products.forEach(function(product) {
                            var productHTML = '<div class="search-res">';
                            productHTML += '<a class="res-ancher" href="' + product.permalink + '"> <img src="'+ product.image +'">';
                            productHTML += '<div class="name-n-price">';
                            productHTML += '<h5>' + product.title + '</h5>';
                            productHTML += '<div class="star-ratings" data-rating="' + product.rating + '"></div>'; // Display star ratings
                            productHTML += '<p>' + product.price + '</p>';
                            productHTML += '</div></a><br></div>';
                            resultsContainer.append(productHTML);
                        });
                
                        // Initialize star ratings after appending products
                        $('.star-ratings').each(function() {
                            var rating = $(this).data('rating');
                            $(this).rateYo({
                                rating: rating,
                                readOnly: true,
                                starWidth: '16px'
                            });
                        });
                    } else {
                        resultsContainer.text('No products found.');
                    }
                }
                ,
                
                error: function(error) {
                    console.log(error.responseText);
                }
            });
        } else {
            // Clear results if search term is less than 3 characters
            $('#search-results').empty();
        }
    });
});
