/* unveil */
/* extend jquery with inview selector which we need to trigger load on ios before scroll. */
$.extend($.expr[':'], {
  inview: function ( el ) {
    var $e = $( el ),
        $w = $( window ),
        wt = $w.scrollTop(),
        wb = wt + $w.height(),
        et = $e.offset().top,
        eb = et + $e.height();
    return eb >= wt && et <= wb;
  } 
});

$(document).ready(function() {

  /* Unveil */
  $("img").unveil(200); /* 200 px before user scroll to them */
  $(window).trigger("unveil"); /* force show stuff in viewport. */

   /* PhotoSwipe */
  var parseElement = function(el) {
    return {
      idx: parseInt(el.attr('data-idx'), 10),
      src: el.attr('data-ps-src'),
      w: parseInt(el.attr('data-ps-width'), 10),
      h: parseInt(el.attr('data-ps-height'), 10)
    };
  }

  var sortPhotos = function(a, b) {
    if (a.idx == b.idx) return 0;
    return a.idx > b.idx ? 1 : -1;
  }

  var fetchItems = function() {
    var items = [];
    $.each($('div[class="item"] img'), function() {
      items.push(parseElement($(this)));
    });
    items.sort(sortPhotos);
    return items;
  };

  var items = fetchItems();

  var openGallery = function(item) {
    var options = {
      index: item.idx - 1,

      getThumbBoundsFn: function(index) {
	var thumbnail = document.querySelectorAll('.img-thumbnail')[index];
        var pageYScroll = window.pageYOffset || document.documentElement.scrollTop; 
	var rect = thumbnail.getBoundingClientRect(); 
	return {x:rect.left, y:rect.top + pageYScroll, w:rect.width};
      }

    };
    var pswpElement = document.querySelectorAll('.pswp')[0];
    var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
    gallery.init();

  };

  // Bind gallery event to all thumbs.
  $('div[class="item"] img').click(function() {
    var e = e || window.event;
    e.preventDefault ? e.preventDefault() : e.returnValue = false;
    openGallery(parseElement($(this)));
  });


  // Upload file 

  var uploadFile = function(file, url) {
    var data = new FormData();
    data.append("uploadphoto", file);

    $.ajax({
      xhr: function() {
        var xhr = new window.XMLHttpRequest();
	xhr.upload.addEventListener("progress", function(e) {
	  if (e.lengthComputable) {
            var percent = Math.ceil((e.loaded / e.total) * 100);
            $("#progressbar").attr('aria-valuenow', percent + "%");
            $("#progressbar").css('width', percent + "%");
	  }
	}, false);
	return xhr;
      },
      beforeSend: function () {
        $("#progressbar").attr('aria-valuenow', "0%");
        $("#progressbar").css('width', "0%");
        $("#btn-upload").hide();
        $("#upload-progress").show();
      },
      url: url,
      type: "POST",
      data: data,
      processData: false,
      contentType: false,
      success: function(response) {
        $("#btn-upload").show();
        $("#upload-progress").hide();
      },
      error: function(jqXHR, textStatus, errorMessage) {
        $("#btn-upload").show();
        $("#upload-progress").hide();
	console.log(errorMessage);
      },
      complete: function(jqXHR, textStatus) {
      }
    });

  };

  $("#btn-upload").click(function() {
    $("#uploadphoto").click();
  });

  $("#uploadphoto").change(function(e) {
    var url = "/upload?l=" + window.location.pathname;
    uploadFile(e.target.files[0], url);
  });

});

