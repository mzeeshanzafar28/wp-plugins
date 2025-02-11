jQuery(document).ready(function ($) {

  $("#video_metabox").hide();
  $("#standard_metabox").hide();

  function toggleMetaboxes(selectedCategory) {
    var videoCategories = ["Training Videos", "eFusion", "MX+"];

    if (videoCategories.includes(selectedCategory)) {
      $("#video_metabox").show();
      $("#standard_metabox").hide();
      $("#standard_id").val("");
      $("#standard_type").val("");
      $("#standard_date").val("");
      $("#standard_download_url_text").val("");
      $("#standard_download_url").val("");
    } else {
      $("#video_metabox").hide();
      $("#standard_metabox").show();
      $("#video_url").val("");
    }
  }

  // Initial setup based on the initially selected checkbox
  var selectedCheckbox = $(
    'input[name="tax_input\\[assistant_category\\]\\[\\]"]:checked'
  );
  if (selectedCheckbox.length > 0) {
    var selectedCategory = selectedCheckbox.closest("label").text().trim();
    toggleMetaboxes(selectedCategory);
  }

  $('input[name="tax_input\\[assistant_category\\]\\[\\]"]').change(
    function () {
      var selectedCategory = $(this).closest("label").text().trim();
      $('input[name="tax_input\\[assistant_category\\]\\[\\]"]')
        .not(this)
        .prop("checked", false);
      toggleMetaboxes(selectedCategory);
    });
  var mediaUploader;

  $("#video_media_button").click(function (e) {
    e.preventDefault();

    if (mediaUploader) {
      mediaUploader.open();

      return;
    }

    mediaUploader = wp.media.frames.file_frame = wp.media({
      title: "Choose Video",

      button: {
        text: "Choose Video",
      },

      multiple: false,
    });

    mediaUploader.on("select", function () {
      var attachment = mediaUploader.state().get("selection").first().toJSON();

      $("#video_url").val(attachment.url);
    });

    mediaUploader.open();
  });
});

