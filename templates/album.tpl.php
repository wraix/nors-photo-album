<style>
.upload-progress {
margin-left: 10px;
margin-right: 10px;
  display:none;
}

.progress {
  background: #b1bc9a;
  border: 1px solid #b1bc9a;
  border-radius: 12px;
  height: 20px;
}
.progress-bar-custom {
  background: #433138;
}

div.btn-upload {
  color: #b1bc9a;
  cursor: hand;
  pointer: hand;
  margin-right: 10px;
  margin-bottom: 4px;
}

div.btn-upload:hover {
  color: #433138;
}

div.btn-upload span.upload-icon {
  font-size: 14pt;
}

</style>

<div class="row">

    <form enctype="multipart/form-data" method="post">
      <input style="display:none;" type="file" id="uploadphoto" name="uploadphoto" />
      <div id="btn-upload" class="btn-upload pull-right"><span class="glyphicon glyphicon-floppy-open" aria-hidden="true"></span> Tilføj billede</div>
    </form>

    <div id="upload-progress" class="upload-progress">
      <div id="uploading" class="pull-right" style="margin-left: 12px;color:#b1bc9a;"><span id="progress-icon" class="glyphicon glyphicon-floppy-open" aria-hidden="true"></span></div>      
      <div class="progress pull">
        <div id="progressbar" class="progress-bar progress-bar-custom" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
      </div>
    </div>

</div>

<div class="row">
  <div id="grid" data-columns>

<?php foreach ($photos as $photo) :?>

  <div class="item">
    <!-- <a href="<?php e($photo['src']); ?>" data-size="<?php e($photo['w']); ?>x<?php e($photo['h']); ?>"> -->
      <img src="/spinner.gif" class="img-responsive center-block img-thumbnail" data-src="<?php e($photo['thumb']); ?>" data-idx="<?php e($photo['idx']); ?>" data-ps-width="<?php e($photo['w']); ?>" data-ps-height="<?php e($photo['h']); ?>" data-ps-src="<?php e($photo['src']); ?>" />
    <!-- </a> -->
    <noscript>
      <a href="<?php e($photo['src']); ?>">
        <img src="<?php e($photo['thumb']); ?>" class="img-responsive center-block img-thumbnail" />
      </a>
    </noscript>
  </div>

<?php endforeach; ?>

  </div>
</div>

<!-- Root element of PhotoSwipe. Must have class pswp. -->
<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">

  <!-- Background of PhotoSwipe. 
       It's a separate element as animating opacity is faster than rgba(). -->
  <div class="pswp__bg"></div>

  <!-- Slides wrapper with overflow:hidden. -->
  <div class="pswp__scroll-wrap">

    <!-- Container that holds slides. 
         PhotoSwipe keeps only 3 of them in the DOM to save memory.
         Don't modify these 3 pswp__item elements, data is added later on. -->
    <div class="pswp__container">
      <div class="pswp__item"></div>
      <div class="pswp__item"></div>
      <div class="pswp__item"></div>
    </div>

    <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
    <div class="pswp__ui pswp__ui--hidden">
      <div class="pswp__top-bar">
        <!--  Controls are self-explanatory. Order can be changed. -->
        <div class="pswp__counter"></div>

        <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
        <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
        <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>

        <!-- Preloader demo http://codepen.io/dimsemenov/pen/yyBWoR -->
        <!-- element will get class pswp__preloader--active when preloader is running -->
        <div class="pswp__preloader">
          <div class="pswp__preloader__icn">
            <div class="pswp__preloader__cut">
              <div class="pswp__preloader__donut"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
        <div class="pswp__share-tooltip"></div> 
      </div>

      <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)"></button>
      <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)"></button>

      <div class="pswp__caption">
        <div class="pswp__caption__center"></div>
      </div>
    </div>
  </div>
</div>