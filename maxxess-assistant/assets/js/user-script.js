jQuery(document).ready(function($) {
    $(".assistant-custom-table").parent().addClass("table-1");
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
                        var flag = 0;
                        var productHTML = '';
                        var productHTML2 = '';
                        products.forEach(function(product) {
                            if (product.video_url) {
                                // Display video details
                                productHTML += '<div class="search-res table-1">';
                                productHTML += '<table style="width: 100%;" class="table-1">';
                                productHTML += '<tr><td style="text-align:center;"><b>' + product.title + '</b></td></tr>';
                                productHTML += '<tr>';
                                productHTML += '<td width="40%">';
                                productHTML += '<video controls width="100%" height="100%">';
                                productHTML += '<source src="' + product.video_url + '" type="video/mp4">';
                                productHTML += 'Your browser does not support the video tag.';
                                productHTML += '</video>';
                                productHTML += '</td><td width="60%">';
                                productHTML += '<p>' + product.video_description + '</p>';
                                productHTML += '</td></tr>';
                                productHTML += '</table></div>';
                            } else {
                                // Display standard details
                                if (flag === 0) {
                                    productHTML2 += '<table style="width: 100%;" class="table-1">';
                                    productHTML2 += '<tr>';
                                    productHTML2 += '<th style="text-align: center; width:5%;">ID</th>';
                                    productHTML2 += '<th style="text-align: center; width:5%;">Name</th>';
                                    productHTML2 += '<th style="text-align: center; width:5%;">Type</th>';
                                    productHTML2 += '<th style="text-align: center; width:5%;">Date</th>';
                                    productHTML2 += '<th style="text-align: center; width:5%;">Download</th>';
                                    productHTML2 += '</tr>';
                                    flag = 1;
                                }
                                productHTML2 += '<tr style="width:5%;">';
                                productHTML2 += '<td style="width:5%;">' + product.standard_id + '</td>';
                                productHTML2 += '<td style="width:5%;">' + product.title + '</td>';
                                productHTML2 += '<td style="width:5%;">' + product.standard_type + '</td>';
                                productHTML2 += '<td style="width:5%;">' + product.standard_date + '</td>';
                                productHTML2 += '<td style="width:5%;"><a href="' + product.standard_download_url + '" target="_blank">' + product.standard_download_url_text + '</a></td>';
                                productHTML2 += '</tr>';
                            }
                        });

                        if (productHTML2 !== '') {
                            productHTML += '<div class="search-res table-1">';
                            productHTML += '<table style="width: 100%;" class="table-1">';
                            productHTML += productHTML2;
                            productHTML += '</table></div>';
                        }
						
						
                        resultsContainer.append(productHTML);
//                         $("#search-bar").after(productHTML);
                    } else {
                        resultsContainer.text('No results found.');
                    }
                },
                error: function(error) {
                    console.log(error.responseText);
                }
            });
        } else {
            // Clear results if search term is less than 3 characters
            $('#search-results').empty();
			
        }
    });
	
// 	var wrap = $("#search-bar");
// 	var results = $('#search-results');
//         $(window).on("scroll", function(e) {
//             if (jQuery(this).scrollTop() > 730 ) {
//                 wrap.addClass("fix-search");
// 				results.addClass("fix-results");
// 				if ( wrap.val().trim().length > 3 ){
// 					results.addClass("scroller");
// 				}
				
//             } else {
//                 wrap.removeClass("fix-search");
// 				results.removeClass("fix-results");
// 				results.removeClass("scroller");

//             }
//         });
	
});