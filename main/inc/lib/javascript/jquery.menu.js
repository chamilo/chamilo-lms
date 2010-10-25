$(function() {
 $('#navigation a').stop().animate({'marginLeft':'50px'},1000);
 
 $('#navigation> li').hover(
  function () {
   $('a',$(this)).stop().animate({'marginLeft':'1px'},200);
  },
  function () {
   $('a',$(this)).stop().animate({'marginLeft':'50px'},200);
  }
 );
});