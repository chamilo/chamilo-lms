#ifndef	_MIMETEX
#define	_MIMETEX
/****************************************************************************
 *
 * Copyright(c) 2002-2008, John Forkosh Associates, Inc. All rights reserved.
 *           http://www.forkosh.com   mailto: john@forkosh.com
 * --------------------------------------------------------------------------
 * This file is part of mimeTeX, which is free software. You may redistribute
 * and/or modify it under the terms of the GNU General Public License,
 * version 3 or later, as published by the Free Software Foundation.
 *      MimeTeX is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY, not even the implied warranty of MERCHANTABILITY.
 * See the GNU General Public License for specific details.
 *      By using mimeTeX, you warrant that you have read, understood and
 * agreed to these terms and conditions, and that you possess the legal
 * right and ability to enter into this agreement and to use mimeTeX
 * in accordance with it.
 *      Your mimetex.zip distribution file should contain the file COPYING,
 * an ascii text copy of the GNU General Public License, version 3.
 * If not, point your browser to  http://www.gnu.org/licenses/
 * or write to the Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330,  Boston, MA 02111-1307 USA.
 * --------------------------------------------------------------------------
 *
 * Purpose:	Structures, macros, symbols,
 *		and static font data for mimetex (and friends)
 * 
 * Source:	mimetex.h
 *
 * Notes:     o	#define TEXFONTS before #include "mimetex.h"
 *		if you need the fonttable[] (of fontfamily's) set up.
 *		mimetex.c needs this; other modules probably don't
 *		because they'll call access functions from mimetex.c
 *		that hide the underlying font data
 *
 * --------------------------------------------------------------------------
 * Revision History:
 * 09/18/02	J.Forkosh	Installation.
 * 12/11/02	J.Forkosh	Version 1.00 released.
 * 07/04/03	J.Forkosh	Version 1.01 released.
 * ---
 * 09/06/08	J.Forkosh	Version 1.70 released.
 *
 ***************************************************************************/


/* --------------------------------------------------------------------------
check for compilation by parts (not supported yet)
-------------------------------------------------------------------------- */
/* --- check for (up to) five parts --- */
#if defined(PART1) || defined(PART2) || defined(PART3) \
||  defined(PART4) || defined(PART5)
  #define PARTS
#endif
/* --- default STATIC=static, else set up static for parts --- */
#if defined(PARTS)
  #if defined(PART1)
    #define INITVALS
    #define STATIC /* not static */
  #else
    #define STATIC extern
  #endif
#else
  #define INITVALS
  #if defined(DRIVER)
    #define STATIC static
  #else
    #define STATIC static /* not static (won't work) */
  #endif
#endif
/* --- declare global symbol --- */
#ifdef INITVALS
  #define GLOBAL(type,variable,value) STATIC type variable = value
  /* #define GLOBAL(type,variable,value) STATIC type variable = (value) */
  /* #define SHARED(type,variable,value) type variable = (value) */
#else
  #define GLOBAL(type,variable,value) STATIC type variable
  /* #define SHARED(type,variable,value) STATIC type variable */
#endif


/* --------------------------------------------------------------------------
macros to get/set/unset a single bit (in rasters), and some bitfield macros
-------------------------------------------------------------------------- */
/* --- single-bit operations on a scalar argument (x) --- */
#define get1bit(x,bit)   ( ((x)>>(bit)) & 1 )	/* get the bit-th bit of x */
#define set1bit(x,bit)   ( (x) |=  (1<<(bit)) )	/* set the bit-th bit of x */
#define unset1bit(x,bit) ( (x) &= ~(1<<(bit)) )	/*unset the bit-th bit of x*/
/* --- single-bit operations on a byte-addressable argument (x) --- */
#define getlongbit(x,bit) get1bit(*((x)+(bit)/8),(bit)%8)	/* get bit */
#define setlongbit(x,bit) set1bit(*((x)+(bit)/8),(bit)%8)	/* set bit */
#define unsetlongbit(x,bit) unset1bit(*((x)+(bit)/8),(bit)%8)	/*unset bit*/
/* --- a few bitfield macros --- */
#define	bitmask(nbits)	((1<<(nbits))-1)	/* a mask of nbits 1's */
#define getbitfld(x,bit1,nbits)	(((x)>>(bit1)) & (bitmask(nbits)))

/* --------------------------------------------------------------------------
macros to get/clear/set a single 4-bit nibble (in rasters)
-------------------------------------------------------------------------- */
#define	getnibble(x,i)				/* get i-th 4-bit nibble */ \
	( (i)%2==0? ((x)[(i)/2] & 0xF0) >> 4:	/* left/high nibble */      \
	(x)[(i)/2] & 0x0F )			/* right/low-order nibble */
#define	clearnibble(x,i) ((x)[(i)/2] &= ((i)%2==0?0x0F:0xF0)) /*clear ith*/
#define	setnibble(x,i,n)			/*set ith nibble of x to n*/\
	if ( (i)%2 == 0 )			/* setting left nibble */   \
	  { clearnibble(x,i);			/* first clear the nibble*/ \
	    (x)[(i)/2] |= ((n)&0x0F)<<4; }	/* set high-order 4 bits */ \
	else					/* setting right nibble */  \
	 if ( 1 )				/* dummy -- always true */  \
	  { clearnibble(x,i);			/* first clear the nibble*/ \
	    (x)[(i)/2] |= (n)&0x0F; }		/* set low-order 4 bits */  \
	 else					/* let user supply final ;*/
/* --- macros to get/set/clear byte (format=2) or nibble (format=3) --- */
#define	getbyfmt(fmt,x,i)			/*byte(fmt=2) or nibble(3)*/\
	( ((fmt)==2? ((int)((x)[(i)])) :	/* get full 8-bit byte */   \
	   ((fmt)==3? getnibble(x,i) : 0)) )	/* or 4-bit nibble (err=0)*/
#define	clearbyfmt(fmt,x,i)			/*byte(fmt=2) or nibble(3)*/\
	if((fmt)==2) (x)[(i)] = ((unsigned char)0); /* clear 8-bit byte */  \
	else if((fmt)==3) clearnibble(x,i)	/* or clear 4-bit nibble */
#define	setbyfmt(fmt,x,i,n)			/*byte(fmt=2) or nibble(3)*/\
	if((fmt)==2) (x)[(i)] = ((unsigned char)n); /*set full 8-bit byte*/ \
	else if((fmt)==3) setnibble(x,i,n); else /* or set 4-bit nibble */

/* -------------------------------------------------------------------------
Raster structure (bitmap or bytemap, along with its width and height in bits)
-------------------------------------------------------------------------- */
/* --- 8-bit datatype (always unsigned) --- */
#define intbyte  unsigned char
/* --- datatype for pixels --- */
/* --- #if !defined(UNSIGNEDCHAR) && !defined(SIGNEDCHAR)
          #define SIGNEDCHAR
       #endif --- */
#ifndef	SIGNEDCHAR
  #define pixbyte  unsigned char
#else
  #define pixbyte  char
#endif
/* --- raster structure --- */
#define	raster	struct raster_struct	/* "typedef" for raster_struct*/
raster
  {
  /* -----------------------------------------------------------------------
  dimensions of raster
  ------------------------------------------------------------------------ */
  int	width;				/* #pixels wide */
  int	height;				/* #pixels high */
  int	format;				/* 1=bitmap, 2=gf/8bits,3=gf/4bits */
  int	pixsz;				/* #bits per pixel, 1 or 8 */
  /* -----------------------------------------------------------------------
  memory for raster
  ------------------------------------------------------------------------ */
  pixbyte *pixmap;		/* memory for width*height bits or bytes */
  } ; /* --- end-of-raster_struct --- */

/* ---
 * associated raster constants and macros
 * -------------------------------------- */
#define	maxraster 1048576 /*99999*/	/* max #pixels for raster pixmap */
/* --- #bytes in pixmap raster needed to contain width x height pixels --- */
#define	bitmapsz(width,height) (((width)*(height)+7)/8) /*#bytes if a bitmap*/
#define	pixmapsz(rp) (((rp)->pixsz)*bitmapsz((rp)->width,(rp)->height))
/* --- #bytes in raster struct, by its format --- */
#define	pixbytes(rp) ((rp)->format==1? pixmapsz(rp) : /*#bytes in bitmap*/  \
	((rp)->format==2? (rp)->pixsz : (1+(rp)->pixsz)/2) ) /*gf-formatted*/
/* --- pixel index calculation used by getpixel() and setpixel() below --- */
#define	PIXDEX(rp,irow,icol) (((irow)*((rp)->width))+(icol))/*irow,icol indx*/
/* --- get value of pixel, either one bit or one byte, at (irow,icol) --- */
#define	getpixel(rp,irow,icol)		/*get bit or byte based on pixsz*/  \
	((rp)->pixsz==1? getlongbit((rp)->pixmap,PIXDEX(rp,(irow),(icol))) :\
	 ((rp)->pixsz==8? ((rp)->pixmap)[PIXDEX(rp,(irow),(icol))] : (-1)) )
/* --- set value of pixel, either one bit or one byte, at (irow,icol) --- */
#define	setpixel(rp,irow,icol,value)	/*set bit or byte based on pixsz*/  \
	if ( (rp)->pixsz == 1 )		/*set pixel to 1 or 0 for bitmap*/  \
	 if ( (value) != 0 )		/* turn bit pixel on */             \
	  { setlongbit((rp)->pixmap,PIXDEX(rp,(irow),(icol))); }            \
	 else				/* or turn bit pixel 0ff */         \
	  { unsetlongbit((rp)->pixmap,PIXDEX(rp,(irow),(icol))); }	    \
	else				/* set 8-bit bytemap pixel value */ \
	  if ( (rp)->pixsz == 8 )	/* check pixsz=8 for bytemap */	    \
	     ((rp)->pixmap)[PIXDEX(rp,(irow),(icol))]=(pixbyte)(value);     \
	  else				/* let user supply final ; */

/* --------------------------------------------------------------------------
some char classes tokenizer needs to recognize, and macros to check for them
-------------------------------------------------------------------------- */
/* --- some character classes --- */
#define	istextmode	(fontinfo[fontnum].istext==1) /* true for text font*/
#define	WHITEMATH	"~ \t\n\r\f\v"	/* white chars in display/math mode*/
#define	WHITETEXT	"\t\n\r\f\v"	/* white chars in text mode */
#define	WHITEDELIM	"~ "		/*always ignored following \sequence*/
#define	WHITESPACE	(istextmode?WHITETEXT:WHITEMATH) /*whitespace chars*/
#define	LEFTBRACES	"{([<|-="	/* opening delims are left{([< |,|| */
#define	RIGHTBRACES	"})]>|-="	/* corresponding closing delims */
#define	ESCAPE		"\\"		/* introduce escape sequence */
#define	SUPERSCRIPT	"^"		/* introduce superscript */
#define	SUBSCRIPT	"_"		/* introduce subscript */
#define	SCRIPTS		SUPERSCRIPT SUBSCRIPT /* either "script" */
/* --- macros to check for them --- */
#define	isthischar(thischar,accept) \
	( (thischar)!='\000' && *(accept)!='\000' \
	&& strchr(accept,(thischar))!=(char *)NULL )
#define	isthisstr(thisstr,accept) \
	((*(thisstr))!='\000' && strspn(thisstr,accept)==strlen(thisstr))
#define	skipwhite(thisstr)  if ( (thisstr) != NULL ) \
	while ( isthischar(*(thisstr),WHITESPACE) ) (thisstr)++
#define	isnextchar(thisstr,accept) \
	({skipwhite(thisstr);},isthischar(*thisstr,accept))

/* -------------------------------------------------------------------------
character definition struct (font info from .gf file describing a char)
-------------------------------------------------------------------------- */
#define	chardef	struct chardef_struct	/* "typedef" for chardef_struct*/
chardef
  {
  /* -----------------------------------------------------------------------
  character description
  ------------------------------------------------------------------------ */
  /* --- character identification as given in .gf font file --- */
  int	charnum;			/*different gf files resuse same num*/
  int	location;			/* location in font */
  /* --- upper-left and lower-left corners of char (topcol=botcol?) --- */
  int	toprow, topleftcol;		/* upper-left corner */
  int	botrow, botleftcol;		/* lower-left corner */
  /* -----------------------------------------------------------------------
  character bitmap raster (image.width is character width, ditto height)
  ------------------------------------------------------------------------ */
  raster  image;			/* bitmap image of character */
  } ; /* --- end-of-chardef_struct --- */


/* -------------------------------------------------------------------------
Font info corresponding to TeX \matchardef, see TeXbook Appendix F (page 431)
-------------------------------------------------------------------------- */
typedef void *((*HANDLER)());		/* ptr to function returning void* */
#define	mathchardef	struct mathchardef_struct /*typedef for mathchardef*/
mathchardef
  {
  /* -----------------------------------------------------------------------
  symbol name ("a", "\alpha", "1", etc)
  ------------------------------------------------------------------------ */
  char	*symbol;			/* as it appears in a source file */
  /* -----------------------------------------------------------------------
  components of \mathchardef hexadecimal code assigned to this symbol
  ------------------------------------------------------------------------ */
  int	charnum;			/* char# (as given in .gf file) */
  int	family;				/* font family e.g., 2=math symbol */
  int	class;				/* e.g., 3=relation, TexBook pg.154*/
  /* ------------------------------------------------------------------------
  Extra info: some math "functions" require special processing (e.g., \frac)
  ------------------------------------------------------------------------ */
  /* --- function that performs special processing required by symbol --- */
  /* subraster *((*handler)()); -- handler is ultimately recast like this */
  HANDLER handler;			/* e.g., rastfrac() for \frac's */
  } ; /* --- end-of-mathchardef_struct --- */

/* ---
 * classes for mathchardef (TeXbook pg.154)
 * ---------------------------------------- */
#define	ORDINARY	(0)		/* e.g., /    */
#define	OPERATOR	(1)		/* e.g., \sum */
#define	BINARYOP	(2)		/* e.g., +    */
#define	RELATION	(3)		/* e.g., =    */
#define	OPENING		(4)		/* e.g., (    */
#define	CLOSING		(5)		/* e.g., }    */
#define	PUNCTION	(6)		/* e.g., , (punctuation) */
#define	VARIABLE	(7)		/* e.g., x    */
#define	DISPOPER	(8)		/* e.g., Bigint (displaymath opers)*/
#define	SPACEOPER	(9)		/* e.g., \hspace{} */
#define	MAXCLASS	(9)		/* just for index checks */
#define	UPPERBIG	DISPOPER	/*how to interpret Bigxxx operators*/
#define	LOWERBIG	DISPOPER	/*how to interpret bigxxx operators*/
/* --- class aliases --- */
#define	ARROW		RELATION
/* --- families for mathchardef (TeXbook, top of pg.431) --- */
#define	CMR10		(1)		/* normal roman */
#define	CMMI10		(2)		/* math italic */
#define	CMMIB10		(3)		/* math italic bold */
#define	CMSY10		(4)		/* math symbol */
#define	CMEX10		(5)		/* math extension */
#define	RSFS10		(6)		/* rsfs \scrA ... \scrZ */
#define	BBOLD10		(7)		/* blackboard bold \mathbb A ... */
#define	STMARY10	(8)		/* stmaryrd math symbols */
#define	CYR10		(9)		/* cyrillic (wncyr10.mf) */
#define	NOTACHAR	(99)		/* e.g., \frac */
/* --- dummy argument value for handlers --- */
#define	NOVALUE		(-989898)	/*charnum,family,class used as args*/

/* ---
 * font family information
 * ----------------------- */
STATIC	int  nfontinfo			/* legal font#'s are 1...nfontinfo */
#ifdef INITVALS
  = 8
#endif
  ;
STATIC	struct {char *name; int family; int istext; int class;}
  /* note: class(1=upper,2=alpha,3=alnum,4=lower,5=digit,9=all) now unused */
  fontinfo[]
#ifdef INITVALS
  = {/* --- name family istext class --- */
    { "\\math",	   0,       0,  0 }, /*(0) default math mode */
    { "\\mathcal", CMSY10,  0,  1 }, /*(1) calligraphic, uppercase */
    { "\\mathscr", RSFS10,  0,  1 }, /*(2) rsfs/script, uppercase */
    { "\\textrm",  CMR10,   1, -1 }, /*(3) \rm,\text{abc} --> {\textrm~abc}*/
    { "\\textit",  CMMI10,  1, -1 }, /*(4) \it,\textit{abc}-->{\textit~abc}*/
    { "\\mathbb",  BBOLD10, 0, -1 }, /*(5) \bb,\mathbb{abc}-->{\mathbb~abc}*/
    { "\\mathbf",  CMMIB10, 0, -1 }, /*(6) \bf,\mathbf{abc}-->{\mathbf~abc}*/
    { "\\mathrm",  CMR10,   0, -1 }, /*(7) \mathrm */
    { "\\cyr",     CYR10,   1, -1 }, /*(8) \cyr (defaults as text mode) */
    {  NULL,	   0,       0,  0 } }
#endif
  ; /* --- end-of-fonts[] --- */

/* ---
 * additional font attributes (only size is implemented)
 * ----------------------------------------------------- */
/* --- font sizes 0-7 = tiny,small,normal,large,Large,LARGE,huge,Huge --- */
#define	LARGESTSIZE	(7)
#ifdef DEFAULTSIZE
  #ifndef NORMALSIZE
    #define NORMALSIZE (DEFAULTSIZE)
  #endif
#endif
#ifndef	NORMALSIZE
  /*#define NORMALSIZE	(2)*/
  #define NORMALSIZE	(3)
#endif
#ifndef	DISPLAYSIZE
  /* --- automatically sets scripts in \displaystyle when fontsize>= --- */
  /*#define DISPLAYSIZE	(NORMALSIZE+1)*/
  #define DISPLAYSIZE	(3)
#endif

/* ---
aspect ratio is width/height of the displayed image of a pixel
-------------------------------------------------------------- */
#define	ASPECTRATIO	1.0 /*(16.0/9.0)*/
#define	SQRTWIDTH(sqrtht) ((int)(.5*((double)(sqrtht+1))*ASPECTRATIO + 0.5))

/* ---
 * space between adjacent symbols, e.g., symspace[RELATION][VARIABLE]
 * ------------------------------------------------------------------ */
STATIC	int symspace[11][11]
#ifdef INITVALS
 =
 { /* -----------------------------------------------------------------------
         Right... ORD OPER  BIN  REL OPEN CLOS PUNC  VAR DISP SPACE unused
    Left... -------------------------------------------------------------- */
  /*ORDINARY*/	{  2,   3,   3,   5,   3,   2,   2,   2,   3,   0,    0 },
  /*OPERATOR*/	{  3,   1,   1,   5,   3,   2,   2,   2,   3,   0,    0 },
  /*BINARYOP*/	{  2,   1,   1,   5,   3,   2,   2,   2,   3,   0,    0 },
  /*RELATION*/	{  5,   5,   5,   2,   5,   5,   2,   5,   5,   0,    0 },
   /*OPENING*/	{  2,   2,   2,   5,   2,   4,   2,   2,   3,   0,    0 },
   /*CLOSING*/	{  2,   3,   3,   5,   4,   2,   1,   2,   3,   0,    0 },
  /*PUNCTION*/	{  2,   2,   2,   5,   2,   2,   1,   2,   2,   0,    0 },
  /*VARIABLE*/	{  2,   2,   2,   5,   2,   2,   1,   2,   2,   0,    0 },
  /*DISPOPER*/	{  2,   3,   3,   5,   2,   3,   2,   2,   2,   0,    0 },
 /*SPACEOPER*/	{  0,   0,   0,   0,   0,   0,   0,   0,   0,   0,    0 },
    /*unused*/	{  0,   0,   0,   0,   0,   0,   0,   0,   0,   0,    0 }
 }
#endif
 ; /* --- end-of-symspace[][] --- */


/* -------------------------------------------------------------------------
subraster (bitmap image, its attributes, overlaid position in raster, etc)
-------------------------------------------------------------------------- */
#define	subraster struct subraster_struct /* "typedef" for subraster_struct*/
subraster
  {
  /* --- subraster type --- */
  int	type;				/* charcter or image raster */
  /* --- character info (if subraster represents a character) --- */
  mathchardef *symdef;			/* mathchardef identifying image */
  int	baseline;			/*0 if image is entirely descending*/
  int	size;				/* font size 0-4 */
  /* --- upper-left corner for bitmap (as overlaid on a larger raster) --- */
  int	toprow, leftcol;		/* upper-left corner of subraster */
  /* --- pointer to raster bitmap image of subraster --- */
  raster *image;			/*ptr to bitmap image of subraster*/
  } ; /* --- end-of-subraster_struct --- */

/* --- subraster types --- */
#define	CHARASTER	(1)		/* character */
#define	STRINGRASTER	(2)		/* string of characters */
#define	IMAGERASTER	(3)		/* image */
#define	FRACRASTER	(4)		/* image of \frac{}{} */
#define	ASCIISTRING	(5)		/* ascii string (not a raster) */

/* ---
 * issue rasterize() call end extract embedded raster from returned subraster
 * -------------------------------------------------------------------------- */
subraster *rasterize();			/* declare rasterize */
#define	make_raster(expression,size)	((rasterize(expression,size))->image)


/* -------------------------------------------------------------------------
font family
-------------------------------------------------------------------------- */
#define	fontfamily	struct fontfamily_struct /* typedef for fontfamily */
fontfamily
  {
  /* -----------------------------------------------------------------------
  several sizes, fontdef[0-7]=tiny,small,normal,large,Large,LARGE,huge,HUGE
  ------------------------------------------------------------------------ */
  int	family;				/* font family e.g., 2=math symbol */
  chardef *fontdef[LARGESTSIZE+2];	/*small=(fontdef[1])[charnum].image*/
  } ; /* --- end-of-fontfamily_struct --- */
/* --- dummy font table (for contexts requiring const) --- */
#define dummyfonttable \
  { \
   {   -999, {  NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL  } } \
  }


/* -------------------------------------------------------------------------
S t a t i c   F o n t   D a t a   u s e d   b y   M i m e t e x
-------------------------------------------------------------------------- */
#ifdef TEXFONTS
/* ---
 * font info generated for us by gfuntype
 * -------------------------------------- */
#ifdef INITVALS
  #include "texfonts.h"
#endif

/* ---
 * font families (by size), just a table of preceding font info
 * ------------------------------------------------------------ */
/* --- for low-pass anti-aliasing --- */
STATIC	fontfamily aafonttable[]
#ifdef INITVALS
 =
 {/* -----------------------------------------------------------------------------------------
    family     size=0,        1,        2,        3,        4,        5,        6,        7
  ----------------------------------------------------------------------------------------- */
  {   CMR10,{   cmr83,   cmr100,   cmr118,   cmr131,   cmr160,   cmr180,   cmr210,   cmr250}},
  {  CMMI10,{  cmmi83,  cmmi100,  cmmi118,  cmmi131,  cmmi160,  cmmi180,  cmmi210,  cmmi250}},
  { CMMIB10,{ cmmib83, cmmib100, cmmib118, cmmib131, cmmib160, cmmib180, cmmib210, cmmib250}},
  {  CMSY10,{  cmsy83,  cmsy100,  cmsy118,  cmsy131,  cmsy160,  cmsy180,  cmsy210,  cmsy250}},
  {  CMEX10,{  cmex83,  cmex100,  cmex118,  cmex131,  cmex160,  cmex180,  cmex210,  cmex250}},
  {  RSFS10,{  rsfs83,  rsfs100,  rsfs118,  rsfs131,  rsfs160,  rsfs180,  rsfs210,  rsfs250}},
  { BBOLD10,{ bbold83, bbold100, bbold118, bbold131, bbold160, bbold180, bbold210, bbold250}},
  {STMARY10,{stmary83,stmary100,stmary118,stmary131,stmary160,stmary180,stmary210,stmary250}},
  {   CYR10,{ wncyr83, wncyr100, wncyr118, wncyr131, wncyr160, wncyr180, wncyr210, wncyr250}},
  {    -999,{    NULL,     NULL,     NULL,     NULL,     NULL,     NULL,     NULL,     NULL}}
 }
#endif
 ; /* --- end-of-aafonttable[] --- */

/* --- for super-sampling anti-aliasing --- */
#ifdef SSFONTS
 STATIC fontfamily ssfonttable[]
 #ifdef INITVALS
  =
  {/* -----------------------------------------------------------------------------------------
    family     size=0,        1,        2,        3,        4,        5,        6,        7
   ----------------------------------------------------------------------------------------- */
   {  CMR10,{  cmr250,  cmr1200,  cmr1200,  cmr1200,  cmr1200,  cmr1200,  cmr1200,  cmr1200}},
   { CMMI10,{ cmmi250,  cmmi100,  cmmi118,  cmmi131,  cmmi160,  cmmi180,  cmmi210,  cmmi250}},
   {CMMIB10,{cmmib250, cmmib100, cmmib118, cmmib131, cmmib160, cmmib180, cmmib210, cmmib250}},
   { CMSY10,{ cmsy250,  cmsy100,  cmsy118,  cmsy131,  cmsy160,  cmsy180,  cmsy210,  cmsy250}},
   { CMEX10,{ cmex250,  cmex100,  cmex118,  cmex131,  cmex160,  cmex180,  cmex210,  cmex250}},
   { RSFS10,{ rsfs250,  rsfs100,  rsfs118,  rsfs131,  rsfs160,  rsfs180,  rsfs210,  rsfs250}},
  { BBOLD10,{bbold250, bbold100, bbold118, bbold131, bbold160, bbold180, bbold210, bbold250}},
 {STMARY10,{stmary250,stmary100,stmary118,stmary131,stmary160,stmary180,stmary210,stmary250}},
  {   CYR10,{ wncyr83, wncyr100, wncyr118, wncyr131, wncyr160, wncyr180, wncyr210, wncyr250}},
   {   -999,{    NULL,     NULL,     NULL,     NULL,     NULL,     NULL,     NULL,     NULL}}
  }
 #endif
  ; /* --- end-of-ssfonttable[] --- */
#else
 /*GLOBAL(fontfamily,ssfonttable[],dummyfonttable);*/
 STATIC fontfamily ssfonttable[]
 #ifdef INITVALS
  = dummyfonttable
 #endif
  ;
#endif  /* #ifdef SSFONTS */
#else
 /*GLOBAL(fontfamily,aafonttable[],dummyfonttable);*/
 /*GLOBAL(fontfamily,ssfonttable[],dummyfonttable);*/
 STATIC fontfamily
	aafonttable[]
	#ifdef INITVALS
	 = dummyfonttable
	#endif
	,
	ssfonttable[]
	#ifdef INITVALS
	 = dummyfonttable
	#endif
	;
#endif  /* #ifdef TEXFONTS */

/* --- select current font table (for lowpass or supersampling) --- */
#ifndef ISSUPERSAMPLING
  #define ISSUPERSAMPLING 0
#endif
GLOBAL(fontfamily,*fonttable,(ISSUPERSAMPLING?ssfonttable:aafonttable));

/* --- supersampling shrink factors corresponding to displayed sizes --- */
STATIC	int shrinkfactors[]		/*supersampling shrinkfactor by size*/
#ifdef INITVALS
 =
    {  3, 3, 3, 3, 3, 3, 3, 3, 2, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1 }
  /*{ 15,13,11, 9, 7, 5, 3, 1 }*/
#endif
 ;

/* ---
 * handler functions for math operations
 * ------------------------------------- */
subraster *rastflags();			/* set flags, e.g., for \rm */
subraster *rastfrac();			/* handle \frac \atop expressions */
subraster *rastackrel();		/* handle \stackrel expressions */
subraster *rastmathfunc();		/* handle \lim,\log,etc expressions*/
subraster *rastoverlay();		/* handle \not */
subraster *rastspace();			/* handle math space, \hspace,\hfill*/
subraster *rastnewline();		/* handle \\ newline */
subraster *rastarrow();			/* handle \longrightarrow, etc */
subraster *rastuparrow();		/* handle \longuparrow, etc */
subraster *rastsqrt();			/* handle \sqrt */
subraster *rastaccent();		/* handle \hat \vec \braces, etc */
subraster *rastfont();			/* handle \cal{} \scr{}, etc */
subraster *rastbegin();			/* handle \begin{}...\end{} */
subraster *rastleft();			/* handle \left...\right */
subraster *rastmiddle();		/* handle \left...\middle...\right */
subraster *rastarray();			/* handle \array{...} */
subraster *rastpicture();		/* handle \picture(,){...} */
subraster *rastline();			/* handle \line(xinc,yinc){xlen} */
subraster *rastrule();			/* handle \rule[lift]{width}{height}*/
subraster *rastcircle();		/* handle \circle(xdiam[,ydiam]) */
subraster *rastbezier();		/*handle\bezier(c0,r0)(c1,r1)(ct,rt)*/
subraster *rastraise();			/* handle \raisebox{lift}{expr} */
subraster *rastrotate();		/* handle \rotatebox{degs}{expr} */
subraster *rastreflect();		/* handle \reflectbox[axis]{expr} */
subraster *rastfbox();			/* handle \fbox{expr} */
subraster *rastinput();			/* handle \input{filename} */
subraster *rastcounter();		/* handle \counter{filename} */
subraster *rasttoday();			/* handle \today[+/-tzdelta,ifmt] */
subraster *rastcalendar();		/* handle \calendar[yaer,month] */
subraster *rastnoop();			/* handle \escape's to be flushed */

/* --- sqrt --- */
#define	SQRTACCENT	(1)		/* \sqrt */
/* --- accents --- */
#define	BARACCENT	(11)		/* \bar \overline*/
#define	UNDERBARACCENT	(12)		/* \underline */
#define	HATACCENT	(13)		/* \hat */
#define	DOTACCENT	(14)		/* \dot */
#define	DDOTACCENT	(15)		/* \ddot */
#define	VECACCENT	(16)		/* \vec */
#define	TILDEACCENT	(17)		/* \tilde */
#define	OVERBRACE	(18)		/* \overbrace */
#define	UNDERBRACE	(19)		/* \underbrace */
/* --- flags/modes --- */
#define	ISFONTFAM	(1)		/* set font family */
#define	ISDISPLAYSTYLE	(2)		/* set isdisplaystyle */
#define	ISDISPLAYSIZE	(21)		/* set displaysize */
#define	ISFONTSIZE	(3)		/* set fontsize */
#define	ISWEIGHT	(4)		/* set aa params */
#define	ISOPAQUE	(5)		/* set background opaque */
#define	ISSUPER		(6)		/* set supersampling/lowpass */
#define	ISAAALGORITHM	(61)		/* set anti-aliasing algorithm */
#define	ISCENTERWT	(62)		/* set anti-aliasing center weight */
#define	ISADJACENTWT	(63)		/* set anti-aliasing adjacent weight*/
#define	ISCORNERWT	(64)		/* set anti-aliasing adjacent weight*/
#define	PNMPARAMS	(65)		/* set fgalias,fgonly,bgalias,bgonly*/
#define	ISGAMMA		(66)		/* set gamma correction */
#define	ISSHRINK	(7)		/* set supersampling shrinkfactor */
#define	UNITLENGTH	(8)		/* set unitlength */
#define	ISCOLOR		(9)		/* set color */
#define	ISREVERSE	(10)		/* set reverse video colors */
#define	ISSTRING	(11)		/* set ascii string mode */
#define	ISSMASH		(12)		/* set (minimum) "smash" margin */
#define	ISCONTENTTYPE	(13)		/*enable/disable Content-type lines*/

/* ---
 * mathchardefs for symbols recognized by mimetex
 * ---------------------------------------------- */
STATIC	mathchardef symtable[]
#ifdef INITVALS
 =
 {
    /* ---------- c o m m a n d  h a n d l e r s --------------
          symbol    arg1     arg2     arg3       function
    -------------------------------------------------------- */
    /* --- commands --- */
    { "\\left", NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rastleft) },
    { "\\middle",NOVALUE,NOVALUE,NOVALUE, (HANDLER)(rastmiddle) },
    { "\\frac",   1,	NOVALUE,NOVALUE,  (HANDLER)(rastfrac) },
    { "\\over",   1,	NOVALUE,NOVALUE,  (HANDLER)(rastfrac) },
    { "\\atop",   0,	NOVALUE,NOVALUE,  (HANDLER)(rastfrac) },
    { "\\choose", 0,	NOVALUE,NOVALUE,  (HANDLER)(rastfrac) },
    { "\\not",    1,          0,NOVALUE,  (HANDLER)(rastoverlay) },
    { "\\Not",    2,          0,NOVALUE,  (HANDLER)(rastoverlay) },
    { "\\widenot",2,          0,NOVALUE,  (HANDLER)(rastoverlay) },
    { "\\sout",   3,    NOVALUE,NOVALUE,  (HANDLER)(rastoverlay) },
    { "\\strikeout",3,  NOVALUE,NOVALUE,  (HANDLER)(rastoverlay) },
    { "\\compose",NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastoverlay) },
    { "\\stackrel", 2,	NOVALUE,NOVALUE,  (HANDLER)(rastackrel) },
    { "\\relstack", 1,	NOVALUE,NOVALUE,  (HANDLER)(rastackrel) },
    { "\\sqrt",	NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rastsqrt) },
    { "\\overbrace",  OVERBRACE,1,    1,  (HANDLER)(rastaccent) },
    { "\\underbrace",UNDERBRACE,0,    1,  (HANDLER)(rastaccent) },
    { "\\overline",   BARACCENT,1,    0,  (HANDLER)(rastaccent) },
    { "\\underline",UNDERBARACCENT,0, 0,  (HANDLER)(rastaccent) },
    { "\\begin",NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rastbegin) },
    { "\\array",NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rastarray) },
    { "\\matrix",NOVALUE,NOVALUE,NOVALUE, (HANDLER)(rastarray) },
    { "\\tabular",NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastarray) },
    { "\\picture",NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastpicture) },
    { "\\line", NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rastline) },
    { "\\rule", NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rastrule) },
    { "\\circle", NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastcircle) },
    { "\\bezier", NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastbezier) },
    { "\\qbezier",NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastbezier) },
    { "\\raisebox",NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastraise) },
    { "\\rotatebox",NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastrotate) },
    { "\\reflectbox",NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastreflect) },
    { "\\fbox", NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rastfbox) },
    { "\\input",NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rastinput) },
    { "\\today",NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rasttoday) },
    { "\\calendar",NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastcalendar) },
    { "\\counter",NOVALUE,NOVALUE,NOVALUE,(HANDLER)(rastcounter) },
    /* --- spaces --- */
    { "\\/",	1,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\,",	2,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\:",	4,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\;",	6,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\\n",	3,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\\r",	3,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\\t",	3,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
      /*{ "\\~",5,NOVALUE,NOVALUE,(HANDLER)(rastspace) },*/
    { "~",	5,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\ ",	5,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { " ",	5,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\!",	-2,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    /*{ "\\!*",	-2,	     99,NOVALUE,  (HANDLER)(rastspace) },*/
    { "\\quad",	6,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\qquad",10,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\hspace",0,	NOVALUE,NOVALUE,  (HANDLER)(rastspace) },
    { "\\hspace*",0,	     99,NOVALUE,  (HANDLER)(rastspace) },
    { "\\vspace",0,	NOVALUE,      1,  (HANDLER)(rastspace) },
    { "\\hfill",0,	      1,NOVALUE,  (HANDLER)(rastspace) },
    /* --- newline --- */
    { "\\\\",   NOVALUE,NOVALUE,NOVALUE,  (HANDLER)(rastnewline) },
    /* --- arrows --- */
    { "\\longrightarrow",   1,0,NOVALUE,  (HANDLER)(rastarrow) },
    { "\\Longrightarrow",   1,1,NOVALUE,  (HANDLER)(rastarrow) },
    { "\\longleftarrow",   -1,0,NOVALUE,  (HANDLER)(rastarrow) },
    { "\\Longleftarrow",   -1,1,NOVALUE,  (HANDLER)(rastarrow) },
    { "\\longleftrightarrow",0,0,NOVALUE, (HANDLER)(rastarrow) },
    { "\\Longleftrightarrow",0,1,NOVALUE, (HANDLER)(rastarrow) },
    { "\\longuparrow",      1,0,NOVALUE, (HANDLER)(rastuparrow) },
    { "\\Longuparrow",      1,1,NOVALUE, (HANDLER)(rastuparrow) },
    { "\\longdownarrow",   -1,0,NOVALUE, (HANDLER)(rastuparrow) },
    { "\\Longdownarrow",   -1,1,NOVALUE, (HANDLER)(rastuparrow) },
    { "\\longupdownarrow",  0,0,NOVALUE, (HANDLER)(rastuparrow) },
    { "\\Longupdownarrow",  0,1,NOVALUE, (HANDLER)(rastuparrow) },
    /* --- modes and values --- */
    { "\\cal",		  1,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mathcal",	  1,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\scr",		  2,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mathscr",	  2,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mathfrak",	  2,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mathbb",	  5,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\rm",		  3,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\text",		  3,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\textrm",	  3,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mathrm",	  7,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\cyr",		  8,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mathbf",	  6,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\bf",		  6,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mathtt",	  3,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mathsf",	  3,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mbox",		  3,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\operatorname",	  3,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\it",		  4,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\textit",	  4,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\mathit",	  4,	 NOVALUE,NOVALUE, (HANDLER)(rastfont) },
    { "\\rm",	  ISFONTFAM,           3,NOVALUE, (HANDLER)(rastflags) },
    { "\\it",	  ISFONTFAM,           4,NOVALUE, (HANDLER)(rastflags) },
    { "\\sl",	  ISFONTFAM,           4,NOVALUE, (HANDLER)(rastflags) },
    { "\\bb",	  ISFONTFAM,           5,NOVALUE, (HANDLER)(rastflags) },
    { "\\bf",	  ISFONTFAM,           6,NOVALUE, (HANDLER)(rastflags) },
    { "\\text",	  ISFONTFAM,           3,NOVALUE, (HANDLER)(rastflags) },
    { "\\math",	  ISFONTFAM,           0,NOVALUE, (HANDLER)(rastflags) },
    { "\\ascii",     ISSTRING,         1,NOVALUE, (HANDLER)(rastflags) },
    { "\\image",     ISSTRING,         0,NOVALUE, (HANDLER)(rastflags) },
    { "\\limits",    ISDISPLAYSTYLE,   2,NOVALUE, (HANDLER)(rastflags) },
    { "\\nolimits",  ISDISPLAYSTYLE,   0,NOVALUE, (HANDLER)(rastflags) },
    { "\\displaystyle",ISDISPLAYSTYLE, 2,NOVALUE, (HANDLER)(rastflags) },
    { "\\textstyle", ISDISPLAYSTYLE,   0,NOVALUE, (HANDLER)(rastflags) },
    { "\\displaysize",ISDISPLAYSIZE,NOVALUE,NOVALUE,(HANDLER)(rastflags)},
    { "\\tiny",      ISFONTSIZE,       0,NOVALUE, (HANDLER)(rastflags) },
    { "\\scriptsize",ISFONTSIZE,       0,NOVALUE, (HANDLER)(rastflags) },
    { "\\footnotesize",ISFONTSIZE,     1,NOVALUE, (HANDLER)(rastflags) },
    { "\\small",     ISFONTSIZE,       1,NOVALUE, (HANDLER)(rastflags) },
    { "\\normalsize",ISFONTSIZE,       2,NOVALUE, (HANDLER)(rastflags) },
    { "\\large",     ISFONTSIZE,       3,NOVALUE, (HANDLER)(rastflags) },
    { "\\Large",     ISFONTSIZE,       4,NOVALUE, (HANDLER)(rastflags) },
    { "\\LARGE",     ISFONTSIZE,       5,NOVALUE, (HANDLER)(rastflags) },
    { "\\huge",      ISFONTSIZE,       6,NOVALUE, (HANDLER)(rastflags) },
    { "\\Huge",      ISFONTSIZE,       7,NOVALUE, (HANDLER)(rastflags) },
    { "\\HUGE",      ISFONTSIZE,       7,NOVALUE, (HANDLER)(rastflags) },
    { "\\fontsize",  ISFONTSIZE, NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\fs",        ISFONTSIZE, NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\shrinkfactor",ISSHRINK, NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\sf",        ISSHRINK,   NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\light",     ISWEIGHT,         0,NOVALUE, (HANDLER)(rastflags) },
    { "\\regular",   ISWEIGHT,         1,NOVALUE, (HANDLER)(rastflags) },
    { "\\semibold",  ISWEIGHT,         2,NOVALUE, (HANDLER)(rastflags) },
    { "\\bold",      ISWEIGHT,         3,NOVALUE, (HANDLER)(rastflags) },
    { "\\fontweight",ISWEIGHT,   NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\fw",        ISWEIGHT,   NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\centerwt",  ISCENTERWT, NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\adjacentwt",ISADJACENTWT,NOVALUE,NOVALUE,(HANDLER)(rastflags) },
    { "\\cornerwt",  ISCORNERWT, NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\ssampling",  ISSUPER,         1,NOVALUE, (HANDLER)(rastflags) },
    { "\\lowpass",    ISSUPER,         0,NOVALUE, (HANDLER)(rastflags) },
    { "\\aaalg",ISAAALGORITHM,   NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\pnmparams",PNMPARAMS,   NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\gammacorrection",ISGAMMA,NOVALUE,NOVALUE,(HANDLER)(rastflags) },
    { "\\nocontenttype",ISCONTENTTYPE, 0,NOVALUE, (HANDLER)(rastflags) },
    { "\\opaque",    ISOPAQUE,         0,NOVALUE, (HANDLER)(rastflags) },
    { "\\transparent",ISOPAQUE,        1,NOVALUE, (HANDLER)(rastflags) },
    { "\\squash",    ISSMASH,          3,1,       (HANDLER)(rastflags) },
    { "\\smash",     ISSMASH,          3,1,       (HANDLER)(rastflags) },
    { "\\nosquash",  ISSMASH,          0,NOVALUE, (HANDLER)(rastflags) },
    { "\\nosmash",   ISSMASH,          0,NOVALUE, (HANDLER)(rastflags) },
    { "\\squashmargin",ISSMASH,  NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\smashmargin", ISSMASH,  NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\unitlength",UNITLENGTH, NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\reverse",   ISREVERSE,  NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\reversefg", ISREVERSE,        1,NOVALUE, (HANDLER)(rastflags) },
    { "\\reversebg", ISREVERSE,        2,NOVALUE, (HANDLER)(rastflags) },
    { "\\color",     ISCOLOR,    NOVALUE,NOVALUE, (HANDLER)(rastflags) },
    { "\\red",       ISCOLOR,          1,NOVALUE, (HANDLER)(rastflags) },
    { "\\green",     ISCOLOR,          2,NOVALUE, (HANDLER)(rastflags) },
    { "\\blue",      ISCOLOR,          3,NOVALUE, (HANDLER)(rastflags) },
    { "\\black",     ISCOLOR,          0,NOVALUE, (HANDLER)(rastflags) },
    { "\\white",     ISCOLOR,          7,NOVALUE, (HANDLER)(rastflags) },
    /* --- accents --- */
    { "\\vec",	VECACCENT,    1,      0,  (HANDLER)(rastaccent) },
    { "\\widevec", VECACCENT, 1,      0,  (HANDLER)(rastaccent) },
    { "\\bar",	BARACCENT,    1,      0,  (HANDLER)(rastaccent) },
    { "\\widebar", BARACCENT, 1,      0,  (HANDLER)(rastaccent) },
    { "\\hat",	HATACCENT,    1,      0,  (HANDLER)(rastaccent) },
    { "\\widehat", HATACCENT, 1,      0,  (HANDLER)(rastaccent) },
    { "\\tilde", TILDEACCENT, 1,      0,  (HANDLER)(rastaccent) },
    { "\\widetilde",TILDEACCENT,1,    0,  (HANDLER)(rastaccent) },
    { "\\dot",	DOTACCENT,    1,      0,  (HANDLER)(rastaccent) },
    { "\\widedot", DOTACCENT, 1,      0,  (HANDLER)(rastaccent) },
    { "\\ddot",	DDOTACCENT,   1,      0,  (HANDLER)(rastaccent) },
    { "\\wideddot",DDOTACCENT,1,      0,  (HANDLER)(rastaccent) },
    /* --- math functions --- */
    { "\\arccos",	1,   0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\arcsin",	2,   0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\arctan",	3,   0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\arg",		4,   0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\cos",		5,   0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\cosh",		6,   0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\cot",		7,   0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\coth",		8,   0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\csc",		9,   0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\deg",		10,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\det",		11,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\dim",		12,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\exp",		13,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\gcd",		14,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\hom",		15,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\inf",		16,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\ker",		17,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\lg",		18,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\lim",		19,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\liminf",	20,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\limsup",	21,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\ln",		22,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\log",		23,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\max",		24,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\min",		25,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\Pr",		26,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\sec",		27,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\sin",		28,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\sinh",		29,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\sup",		30,  1,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\tan",		31,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\tanh",		32,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\tr",		33,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    { "\\pmod",		34,  0,	NOVALUE,  (HANDLER)(rastmathfunc) },
    /* --- flush -- recognized but not yet handled by mimeTeX --- */
    { "\\nooperation",0,NOVALUE,NOVALUE,  (HANDLER)(rastnoop) },
    { "\\bigskip",   0, NOVALUE,NOVALUE,  (HANDLER)(rastnoop) },
    { "\\phantom",   1, NOVALUE,NOVALUE,  (HANDLER)(rastnoop) },
    { "\\nocaching", 0, NOVALUE,NOVALUE,  (HANDLER)(rastnoop) },
    { "\\noconten",  0, NOVALUE,NOVALUE,  (HANDLER)(rastnoop) },
    { "\\nonumber",  0, NOVALUE,NOVALUE,  (HANDLER)(rastnoop) },
    /* { "\\!",      0, NOVALUE,NOVALUE,  (HANDLER)(rastnoop) }, */
    { "\\cydot",     0, NOVALUE,NOVALUE,  (HANDLER)(rastnoop) },
    /* --------------------- C M M I --------------------------
          symbol     charnum    family    class	    function
    -------------------------------------------------------- */
    /* --- uppercase greek letters --- */
    { "\\Gamma",	0,	CMMI10,   VARIABLE,	NULL },
    { "\\Delta",	1,	CMMI10,   VARIABLE,	NULL },
    { "\\Theta",	2,	CMMI10,   VARIABLE,	NULL },
    { "\\Lambda",	3,	CMMI10,   VARIABLE,	NULL },
    { "\\Xi",		4,	CMMI10,   VARIABLE,	NULL },
    { "\\Pi",		5,	CMMI10,   VARIABLE,	NULL },
    { "\\Sigma",	6,	CMMI10,   VARIABLE,	NULL },
    { "\\smallsum",	6,	CMMI10,   OPERATOR,	NULL },
    { "\\Upsilon",	7,	CMMI10,   VARIABLE,	NULL },
    { "\\Phi",		8,	CMMI10,   VARIABLE,	NULL },
    { "\\Psi",		9,	CMMI10,   VARIABLE,	NULL },
    { "\\Omega",	10,	CMMI10,   VARIABLE,	NULL },
    /* --- lowercase greek letters --- */
    { "\\alpha",	11,	CMMI10,   VARIABLE,	NULL },
    { "\\beta",		12,	CMMI10,   VARIABLE,	NULL },
    { "\\gamma",	13,	CMMI10,   VARIABLE,	NULL },
    { "\\delta",	14,	CMMI10,   VARIABLE,	NULL },
    { "\\epsilon",	15,	CMMI10,   VARIABLE,	NULL },
    { "\\zeta",		16,	CMMI10,   VARIABLE,	NULL },
    { "\\eta",		17,	CMMI10,   VARIABLE,	NULL },
    { "\\theta",	18,	CMMI10,   VARIABLE,	NULL },
    { "\\iota",		19,	CMMI10,   VARIABLE,	NULL },
    { "\\kappa",	20,	CMMI10,   VARIABLE,	NULL },
    { "\\lambda",	21,	CMMI10,   VARIABLE,	NULL },
    { "\\mu",		22,	CMMI10,   VARIABLE,	NULL },
    { "\\nu",		23,	CMMI10,   VARIABLE,	NULL },
    { "\\xi",		24,	CMMI10,   VARIABLE,	NULL },
    { "\\pi",		25,	CMMI10,   VARIABLE,	NULL },
    { "\\rho",		26,	CMMI10,   VARIABLE,	NULL },
    { "\\sigma",	27,	CMMI10,   VARIABLE,	NULL },
    { "\\tau",		28,	CMMI10,   VARIABLE,	NULL },
    { "\\upsilon",	29,	CMMI10,   VARIABLE,	NULL },
    { "\\phi",		30,	CMMI10,   VARIABLE,	NULL },
    { "\\chi",		31,	CMMI10,   VARIABLE,	NULL },
    { "\\psi",		32,	CMMI10,   VARIABLE,	NULL },
    { "\\omega",	33,	CMMI10,   VARIABLE,	NULL },
    { "\\varepsilon",	34,	CMMI10,   VARIABLE,	NULL },
    { "\\vartheta",	35,	CMMI10,   VARIABLE,	NULL },
    { "\\varpi",	36,	CMMI10,   VARIABLE,	NULL },
    { "\\varrho",	37,	CMMI10,   VARIABLE,	NULL },
    { "\\varsigma",	38,	CMMI10,   VARIABLE,	NULL },
    { "\\varphi",	39,	CMMI10,   VARIABLE,	NULL },
    /* --- arrow relations --- */
    { "\\leftharpoonup",    40,	CMMI10,   ARROW,	NULL },
    { "\\leftharpoondown",  41,	CMMI10,   ARROW,	NULL },
    { "\\rightharpoonup",   42,	CMMI10,   ARROW,	NULL },
    { "\\rightharpoondown", 43,	CMMI10,   ARROW,	NULL },
    /* --- punctuation --- */
    { "`",		44,	CMMI10,   PUNCTION,	NULL },
    { "\'",		45,	CMMI10,   PUNCTION,	NULL },
    /* --- triangle binary relations --- */
    { "\\triangleright",    46,	CMMI10,   RELATION,	NULL },
    { "\\triangleleft",     47,	CMMI10,   RELATION,	NULL },
    /* --- digits 0-9 --- */
    { "\\0",		48,	CMMI10,   ORDINARY,	NULL },
    { "\\1",		49,	CMMI10,   ORDINARY,	NULL },
    { "\\2",		50,	CMMI10,   ORDINARY,	NULL },
    { "\\3",		51,	CMMI10,   ORDINARY,	NULL },
    { "\\4",		52,	CMMI10,   ORDINARY,	NULL },
    { "\\5",		53,	CMMI10,   ORDINARY,	NULL },
    { "\\6",		54,	CMMI10,   ORDINARY,	NULL },
    { "\\7",		55,	CMMI10,   ORDINARY,	NULL },
    { "\\8",		56,	CMMI10,   ORDINARY,	NULL },
    { "\\9",		57,	CMMI10,   ORDINARY,	NULL },
    /* --- punctuation --- */
    { ".",		58,	CMMI10,   PUNCTION,	NULL },
    { ",",		59,	CMMI10,   PUNCTION,	NULL },
    /* --- operations (some ordinary) --- */
    { "<",		60,	CMMI10,   OPENING,	NULL },
    { "\\<",		60,	CMMI10,   OPENING,	NULL },
    { "\\lt",		60,	CMMI10,   OPENING,	NULL },
    { "/",		61,	CMMI10,   BINARYOP,	NULL },
    { ">",		62,	CMMI10,   CLOSING,	NULL },
    { "\\>",		62,	CMMI10,   CLOSING,	NULL },
    { "\\gt",		62,	CMMI10,   CLOSING,	NULL },
    { "\\star",		63,	CMMI10,   BINARYOP,	NULL },
    { "\\partial",	64,	CMMI10,   VARIABLE,	NULL },
    /* --- uppercase letters --- */
    { "A",		65,	CMMI10,   VARIABLE,	NULL },
    { "B",		66,	CMMI10,   VARIABLE,	NULL },
    { "C",		67,	CMMI10,   VARIABLE,	NULL },
    { "D",		68,	CMMI10,   VARIABLE,	NULL },
    { "E",		69,	CMMI10,   VARIABLE,	NULL },
    { "F",		70,	CMMI10,   VARIABLE,	NULL },
    { "G",		71,	CMMI10,   VARIABLE,	NULL },
    { "H",		72,	CMMI10,   VARIABLE,	NULL },
    { "I",		73,	CMMI10,   VARIABLE,	NULL },
    { "J",		74,	CMMI10,   VARIABLE,	NULL },
    { "K",		75,	CMMI10,   VARIABLE,	NULL },
    { "L",		76,	CMMI10,   VARIABLE,	NULL },
    { "M",		77,	CMMI10,   VARIABLE,	NULL },
    { "N",		78,	CMMI10,   VARIABLE,	NULL },
    { "O",		79,	CMMI10,   VARIABLE,	NULL },
    { "P",		80,	CMMI10,   VARIABLE,	NULL },
    { "Q",		81,	CMMI10,   VARIABLE,	NULL },
    { "R",		82,	CMMI10,   VARIABLE,	NULL },
    { "S",		83,	CMMI10,   VARIABLE,	NULL },
    { "T",		84,	CMMI10,   VARIABLE,	NULL },
    { "U",		85,	CMMI10,   VARIABLE,	NULL },
    { "V",		86,	CMMI10,   VARIABLE,	NULL },
    { "W",		87,	CMMI10,   VARIABLE,	NULL },
    { "X",		88,	CMMI10,   VARIABLE,	NULL },
    { "Y",		89,	CMMI10,   VARIABLE,	NULL },
    { "Z",		90,	CMMI10,   VARIABLE,	NULL },
    /* --- miscellaneous symbols and relations --- */
    { "\\flat",		91,	CMMI10,   ORDINARY,	NULL },
    { "\\natural",	92,	CMMI10,   ORDINARY,	NULL },
    { "\\sharp",	93,	CMMI10,   ORDINARY,	NULL },
    { "\\smile",	94,	CMMI10,   RELATION,	NULL },
    { "\\frown",	95,	CMMI10,   RELATION,	NULL },
    { "\\ell",		96,	CMMI10,   ORDINARY,	NULL },
    /* --- lowercase letters --- */
    { "a",		97,	CMMI10,   VARIABLE,	NULL },
    { "b",		98,	CMMI10,   VARIABLE,	NULL },
    { "c",		99,	CMMI10,   VARIABLE,	NULL },
    { "d",		100,	CMMI10,   VARIABLE,	NULL },
    { "e",		101,	CMMI10,   VARIABLE,	NULL },
    { "f",		102,	CMMI10,   VARIABLE,	NULL },
    { "g",		103,	CMMI10,   VARIABLE,	NULL },
    { "h",		104,	CMMI10,   VARIABLE,	NULL },
    { "i",		105,	CMMI10,   VARIABLE,	NULL },
    { "j",		106,	CMMI10,   VARIABLE,	NULL },
    { "k",		107,	CMMI10,   VARIABLE,	NULL },
    { "l",		108,	CMMI10,   VARIABLE,	NULL },
    { "m",		109,	CMMI10,   VARIABLE,	NULL },
    { "n",		110,	CMMI10,   VARIABLE,	NULL },
    { "o",		111,	CMMI10,   VARIABLE,	NULL },
    { "p",		112,	CMMI10,   VARIABLE,	NULL },
    { "q",		113,	CMMI10,   VARIABLE,	NULL },
    { "r",		114,	CMMI10,   VARIABLE,	NULL },
    { "s",		115,	CMMI10,   VARIABLE,	NULL },
    { "t",		116,	CMMI10,   VARIABLE,	NULL },
    { "u",		117,	CMMI10,   VARIABLE,	NULL },
    { "v",		118,	CMMI10,   VARIABLE,	NULL },
    { "w",		119,	CMMI10,   VARIABLE,	NULL },
    { "x",		120,	CMMI10,   VARIABLE,	NULL },
    { "y",		121,	CMMI10,   VARIABLE,	NULL },
    { "z",		122,	CMMI10,   VARIABLE,	NULL },
    /* --- miscellaneous symbols and relations --- */
    { "\\imath",	123,	CMMI10,   VARIABLE,	NULL },
    { "\\jmath",	124,	CMMI10,   VARIABLE,	NULL },
    { "\\wp",		125,	CMMI10,   ORDINARY,	NULL },
    { "\\vec",		126,	CMMI10,   ORDINARY,	NULL },
    /* --------------------- C M M I B ------------------------
          symbol     charnum    family    class	    function
    -------------------------------------------------------- */
    /* --- uppercase greek letters --- */
    { "\\Gamma",	0,	CMMIB10,  VARIABLE,	NULL },
    { "\\Delta",	1,	CMMIB10,  VARIABLE,	NULL },
    { "\\Theta",	2,	CMMIB10,  VARIABLE,	NULL },
    { "\\Lambda",	3,	CMMIB10,  VARIABLE,	NULL },
    { "\\Xi",		4,	CMMIB10,  VARIABLE,	NULL },
    { "\\Pi",		5,	CMMIB10,  VARIABLE,	NULL },
    { "\\Sigma",	6,	CMMIB10,  VARIABLE,	NULL },
    { "\\smallsum",	6,	CMMIB10,  OPERATOR,	NULL },
    { "\\Upsilon",	7,	CMMIB10,  VARIABLE,	NULL },
    { "\\Phi",		8,	CMMIB10,  VARIABLE,	NULL },
    { "\\Psi",		9,	CMMIB10,  VARIABLE,	NULL },
    { "\\Omega",	10,	CMMIB10,  VARIABLE,	NULL },
    /* --- lowercase greek letters --- */
    { "\\alpha",	11,	CMMIB10,  VARIABLE,	NULL },
    { "\\beta",		12,	CMMIB10,  VARIABLE,	NULL },
    { "\\gamma",	13,	CMMIB10,  VARIABLE,	NULL },
    { "\\delta",	14,	CMMIB10,  VARIABLE,	NULL },
    { "\\epsilon",	15,	CMMIB10,  VARIABLE,	NULL },
    { "\\zeta",		16,	CMMIB10,  VARIABLE,	NULL },
    { "\\eta",		17,	CMMIB10,  VARIABLE,	NULL },
    { "\\theta",	18,	CMMIB10,  VARIABLE,	NULL },
    { "\\iota",		19,	CMMIB10,  VARIABLE,	NULL },
    { "\\kappa",	20,	CMMIB10,  VARIABLE,	NULL },
    { "\\lambda",	21,	CMMIB10,  VARIABLE,	NULL },
    { "\\mu",		22,	CMMIB10,  VARIABLE,	NULL },
    { "\\nu",		23,	CMMIB10,  VARIABLE,	NULL },
    { "\\xi",		24,	CMMIB10,  VARIABLE,	NULL },
    { "\\pi",		25,	CMMIB10,  VARIABLE,	NULL },
    { "\\rho",		26,	CMMIB10,  VARIABLE,	NULL },
    { "\\sigma",	27,	CMMIB10,  VARIABLE,	NULL },
    { "\\tau",		28,	CMMIB10,  VARIABLE,	NULL },
    { "\\upsilon",	29,	CMMIB10,  VARIABLE,	NULL },
    { "\\phi",		30,	CMMIB10,  VARIABLE,	NULL },
    { "\\chi",		31,	CMMIB10,  VARIABLE,	NULL },
    { "\\psi",		32,	CMMIB10,  VARIABLE,	NULL },
    { "\\omega",	33,	CMMIB10,  VARIABLE,	NULL },
    { "\\varepsilon",	34,	CMMIB10,  VARIABLE,	NULL },
    { "\\vartheta",	35,	CMMIB10,  VARIABLE,	NULL },
    { "\\varpi",	36,	CMMIB10,  VARIABLE,	NULL },
    { "\\varrho",	37,	CMMIB10,  VARIABLE,	NULL },
    { "\\varsigma",	38,	CMMIB10,  VARIABLE,	NULL },
    { "\\varphi",	39,	CMMIB10,  VARIABLE,	NULL },
    /* --- arrow relations --- */
    { "\\bfleftharpoonup",  40,	CMMIB10,  ARROW,	NULL },
    { "\\bfleftharpoondown",41,	CMMIB10,  ARROW,	NULL },
    { "\\bfrightharpoonup", 42,	CMMIB10,  ARROW,	NULL },
    { "\\bfrightharpoondown",43,CMMIB10,  ARROW,	NULL },
    /* --- punctuation --- */
    { "`",		44,	CMMIB10,  PUNCTION,	NULL },
    { "\'",		45,	CMMIB10,  PUNCTION,	NULL },
    /* --- triangle binary relations --- */
    { "\\triangleright",    46,	CMMIB10,  RELATION,	NULL },
    { "\\triangleleft",     47,	CMMIB10,  RELATION,	NULL },
    /* --- digits 0-9 --- */
    { "\\0",		48,	CMMIB10,  ORDINARY,	NULL },
    { "\\1",		49,	CMMIB10,  ORDINARY,	NULL },
    { "\\2",		50,	CMMIB10,  ORDINARY,	NULL },
    { "\\3",		51,	CMMIB10,  ORDINARY,	NULL },
    { "\\4",		52,	CMMIB10,  ORDINARY,	NULL },
    { "\\5",		53,	CMMIB10,  ORDINARY,	NULL },
    { "\\6",		54,	CMMIB10,  ORDINARY,	NULL },
    { "\\7",		55,	CMMIB10,  ORDINARY,	NULL },
    { "\\8",		56,	CMMIB10,  ORDINARY,	NULL },
    { "\\9",		57,	CMMIB10,  ORDINARY,	NULL },
    /* --- punctuation --- */
    { ".",		58,	CMMIB10,  PUNCTION,	NULL },
    { ",",		59,	CMMIB10,  PUNCTION,	NULL },
    /* --- operations (some ordinary) --- */
    { "<",		60,	CMMIB10,  OPENING,	NULL },
    { "\\lt",		60,	CMMIB10,  OPENING,	NULL },
    { "/",		61,	CMMIB10,  BINARYOP,	NULL },
    { ">",		62,	CMMIB10,  CLOSING,	NULL },
    { "\\gt",		62,	CMMIB10,  CLOSING,	NULL },
    { "\\star",		63,	CMMIB10,  BINARYOP,	NULL },
    { "\\partial",	64,	CMMIB10,  VARIABLE,	NULL },
    /* --- uppercase letters --- */
    { "A",		65,	CMMIB10,  VARIABLE,	NULL },
    { "B",		66,	CMMIB10,  VARIABLE,	NULL },
    { "C",		67,	CMMIB10,  VARIABLE,	NULL },
    { "D",		68,	CMMIB10,  VARIABLE,	NULL },
    { "E",		69,	CMMIB10,  VARIABLE,	NULL },
    { "F",		70,	CMMIB10,  VARIABLE,	NULL },
    { "G",		71,	CMMIB10,  VARIABLE,	NULL },
    { "H",		72,	CMMIB10,  VARIABLE,	NULL },
    { "I",		73,	CMMIB10,  VARIABLE,	NULL },
    { "J",		74,	CMMIB10,  VARIABLE,	NULL },
    { "K",		75,	CMMIB10,  VARIABLE,	NULL },
    { "L",		76,	CMMIB10,  VARIABLE,	NULL },
    { "M",		77,	CMMIB10,  VARIABLE,	NULL },
    { "N",		78,	CMMIB10,  VARIABLE,	NULL },
    { "O",		79,	CMMIB10,  VARIABLE,	NULL },
    { "P",		80,	CMMIB10,  VARIABLE,	NULL },
    { "Q",		81,	CMMIB10,  VARIABLE,	NULL },
    { "R",		82,	CMMIB10,  VARIABLE,	NULL },
    { "S",		83,	CMMIB10,  VARIABLE,	NULL },
    { "T",		84,	CMMIB10,  VARIABLE,	NULL },
    { "U",		85,	CMMIB10,  VARIABLE,	NULL },
    { "V",		86,	CMMIB10,  VARIABLE,	NULL },
    { "W",		87,	CMMIB10,  VARIABLE,	NULL },
    { "X",		88,	CMMIB10,  VARIABLE,	NULL },
    { "Y",		89,	CMMIB10,  VARIABLE,	NULL },
    { "Z",		90,	CMMIB10,  VARIABLE,	NULL },
    /* --- miscellaneous symbols and relations --- */
    { "\\flat",		91,	CMMIB10,  ORDINARY,	NULL },
    { "\\natural",	92,	CMMIB10,  ORDINARY,	NULL },
    { "\\sharp",	93,	CMMIB10,  ORDINARY,	NULL },
    { "\\smile",	94,	CMMIB10,  RELATION,	NULL },
    { "\\frown",	95,	CMMIB10,  RELATION,	NULL },
    { "\\ell",		96,	CMMIB10,  ORDINARY,	NULL },
    /* --- lowercase letters --- */
    { "a",		97,	CMMIB10,  VARIABLE,	NULL },
    { "b",		98,	CMMIB10,  VARIABLE,	NULL },
    { "c",		99,	CMMIB10,  VARIABLE,	NULL },
    { "d",		100,	CMMIB10,  VARIABLE,	NULL },
    { "e",		101,	CMMIB10,  VARIABLE,	NULL },
    { "f",		102,	CMMIB10,  VARIABLE,	NULL },
    { "g",		103,	CMMIB10,  VARIABLE,	NULL },
    { "h",		104,	CMMIB10,  VARIABLE,	NULL },
    { "i",		105,	CMMIB10,  VARIABLE,	NULL },
    { "j",		106,	CMMIB10,  VARIABLE,	NULL },
    { "k",		107,	CMMIB10,  VARIABLE,	NULL },
    { "l",		108,	CMMIB10,  VARIABLE,	NULL },
    { "m",		109,	CMMIB10,  VARIABLE,	NULL },
    { "n",		110,	CMMIB10,  VARIABLE,	NULL },
    { "o",		111,	CMMIB10,  VARIABLE,	NULL },
    { "p",		112,	CMMIB10,  VARIABLE,	NULL },
    { "q",		113,	CMMIB10,  VARIABLE,	NULL },
    { "r",		114,	CMMIB10,  VARIABLE,	NULL },
    { "s",		115,	CMMIB10,  VARIABLE,	NULL },
    { "t",		116,	CMMIB10,  VARIABLE,	NULL },
    { "u",		117,	CMMIB10,  VARIABLE,	NULL },
    { "v",		118,	CMMIB10,  VARIABLE,	NULL },
    { "w",		119,	CMMIB10,  VARIABLE,	NULL },
    { "x",		120,	CMMIB10,  VARIABLE,	NULL },
    { "y",		121,	CMMIB10,  VARIABLE,	NULL },
    { "z",		122,	CMMIB10,  VARIABLE,	NULL },
    /* --- miscellaneous symbols and relations --- */
    { "\\imath",	123,	CMMIB10,  VARIABLE,	NULL },
    { "\\jmath",	124,	CMMIB10,  VARIABLE,	NULL },
    { "\\wp",		125,	CMMIB10,  ORDINARY,	NULL },
    { "\\bfvec",	126,	CMMIB10,  ORDINARY,	NULL },
    /* --------------------- C M S Y --------------------------
          symbol     charnum    family    class	    function
    -------------------------------------------------------- */
    /* --- operations --- */
    { "-",		0,	CMSY10,   BINARYOP,	NULL },
    { "\\cdot",		1,	CMSY10,   BINARYOP,	NULL },
    { "\\times",	2,	CMSY10,   BINARYOP,	NULL },
    { "\\ast",		3,	CMSY10,   BINARYOP,	NULL },
    { "\\div",		4,	CMSY10,   BINARYOP,	NULL },
    { "\\diamond",	5,	CMSY10,   BINARYOP,	NULL },
    { "\\pm",		6,	CMSY10,   BINARYOP,	NULL },
    { "\\mp",		7,	CMSY10,   BINARYOP,	NULL },
    { "\\oplus",	8,	CMSY10,   BINARYOP,	NULL },
    { "\\ominus",	9,	CMSY10,   BINARYOP,	NULL },
    { "\\otimes",	10,	CMSY10,   BINARYOP,	NULL },
    { "\\oslash",	11,	CMSY10,   BINARYOP,	NULL },
    { "\\odot",		12,	CMSY10,   BINARYOP,	NULL },
    { "\\bigcirc",	13,	CMSY10,   BINARYOP,	NULL },
    { "\\circ",		14,	CMSY10,   BINARYOP,	NULL },
    { "\\bullet",	15,	CMSY10,   BINARYOP,	NULL },
    /* --- relations --- */
    { "\\asymp",	16,	CMSY10,   RELATION,	NULL },
    { "\\equiv",	17,	CMSY10,   RELATION,	NULL },
    { "\\subseteq",	18,	CMSY10,   RELATION,	NULL },
    { "\\supseteq",	19,	CMSY10,   RELATION,	NULL },
    { "\\leq",		20,	CMSY10,   RELATION,	NULL },
    { "\\geq",		21,	CMSY10,   RELATION,	NULL },
    { "\\preceq",	22,	CMSY10,   RELATION,	NULL },
    { "\\succeq",	23,	CMSY10,   RELATION,	NULL },
    { "\\sim",		24,	CMSY10,   RELATION,	NULL },
    { "\\approx",	25,	CMSY10,   RELATION,	NULL },
    { "\\subset",	26,	CMSY10,   RELATION,	NULL },
    { "\\supset",	27,	CMSY10,   RELATION,	NULL },
    { "\\ll",		28,	CMSY10,   RELATION,	NULL },
    { "\\gg",		29,	CMSY10,   RELATION,	NULL },
    { "\\prec",		30,	CMSY10,   RELATION,	NULL },
    { "\\succ",		31,	CMSY10,   RELATION,	NULL },
    /* --- (mostly) arrows --- */
    { "\\leftarrow",	32,	CMSY10,   ARROW,	NULL },
    { "\\rightarrow",	33,	CMSY10,   ARROW,	NULL },
    { "\\to",		33,	CMSY10,   ARROW,	NULL },
    { "\\mapsto",	33,	CMSY10,   ARROW,	NULL },
    { "\\uparrow",	34,	CMSY10,   ARROW,	NULL },
    { "\\downarrow",	35,	CMSY10,   ARROW,	NULL },
    { "\\leftrightarrow",   36,	CMSY10,   ARROW,	NULL },
    { "\\nearrow",	37,	CMSY10,   ARROW,	NULL },
    { "\\searrow",	38,	CMSY10,   ARROW,	NULL },
    { "\\simeq",	39,	CMSY10,   RELATION,	NULL },
    { "\\Leftarrow",	40,	CMSY10,   ARROW,	NULL },
    { "\\Rightarrow",	41,	CMSY10,   ARROW,	NULL },
    { "\\Uparrow",	42,	CMSY10,   ARROW,	NULL },
    { "\\Downarrow",	43,	CMSY10,   ARROW,	NULL },
    { "\\Leftrightarrow",   44,	CMSY10,   ARROW,	NULL },
    { "\\nwarrow",	45,	CMSY10,   ARROW,	NULL },
    { "\\swarrow",	46,	CMSY10,   ARROW,	NULL },
    { "\\propto",	47,	CMSY10,   RELATION,	NULL },
    /* --- symbols --- */
    { "\\prime",	48,	CMSY10,   ORDINARY,	NULL },
    { "\\infty",	49,	CMSY10,   ORDINARY,	NULL },
    /* --- relations --- */
    { "\\in",		50,	CMSY10,   RELATION,	NULL },
    { "\\ni",		51,	CMSY10,   RELATION,	NULL },
    /* --- symbols --- */
    { "\\triangle",	    52,	CMSY10,   ORDINARY,	NULL },
    { "\\bigtriangleup",    52,	CMSY10,   ORDINARY,	NULL },
    { "\\bigtriangledown",  53,	CMSY10,   ORDINARY,	NULL },
    { "\\boldslash",	54,	CMSY10,   BINARYOP,	NULL },
    { "\\'",		55,	CMSY10,   ORDINARY,	NULL },
    { "\\forall",	56,	CMSY10,   OPERATOR,	NULL },
    { "\\exists",	57,	CMSY10,   OPERATOR,	NULL },
    { "\\neg",		58,	CMSY10,   OPERATOR,	NULL },
    { "\\emptyset",	59,	CMSY10,   ORDINARY,	NULL },
    { "\\Re",		60,	CMSY10,   ORDINARY,	NULL },
    { "\\Im",		61,	CMSY10,   ORDINARY,	NULL },
    { "\\top",		62,	CMSY10,   ORDINARY,	NULL },
    { "\\bot",		63,	CMSY10,   ORDINARY,	NULL },
    { "\\perp",		63,	CMSY10,   BINARYOP,	NULL },
    { "\\aleph",	64,	CMSY10,   ORDINARY,	NULL },
    /* --- calligraphic letters (we use \\calA...\\calZ --- */
    { "\\calA",		65,	CMSY10,   VARIABLE,	NULL },
    { "\\calB",		66,	CMSY10,   VARIABLE,	NULL },
    { "\\calC",		67,	CMSY10,   VARIABLE,	NULL },
    { "\\calD",		68,	CMSY10,   VARIABLE,	NULL },
    { "\\calE",		69,	CMSY10,   VARIABLE,	NULL },
    { "\\calF",		70,	CMSY10,   VARIABLE,	NULL },
    { "\\calG",		71,	CMSY10,   VARIABLE,	NULL },
    { "\\calH",		72,	CMSY10,   VARIABLE,	NULL },
    { "\\calI",		73,	CMSY10,   VARIABLE,	NULL },
    { "\\calJ",		74,	CMSY10,   VARIABLE,	NULL },
    { "\\calK",		75,	CMSY10,   VARIABLE,	NULL },
    { "\\calL",		76,	CMSY10,   VARIABLE,	NULL },
    { "\\calM",		77,	CMSY10,   VARIABLE,	NULL },
    { "\\calN",		78,	CMSY10,   VARIABLE,	NULL },
    { "\\calO",		79,	CMSY10,   VARIABLE,	NULL },
    { "\\calP",		80,	CMSY10,   VARIABLE,	NULL },
    { "\\calQ",		81,	CMSY10,   VARIABLE,	NULL },
    { "\\calR",		82,	CMSY10,   VARIABLE,	NULL },
    { "\\calS",		83,	CMSY10,   VARIABLE,	NULL },
    { "\\calT",		84,	CMSY10,   VARIABLE,	NULL },
    { "\\calU",		85,	CMSY10,   VARIABLE,	NULL },
    { "\\calV",		86,	CMSY10,   VARIABLE,	NULL },
    { "\\calW",		87,	CMSY10,   VARIABLE,	NULL },
    { "\\calX",		88,	CMSY10,   VARIABLE,	NULL },
    { "\\calY",		89,	CMSY10,   VARIABLE,	NULL },
    { "\\calZ",		90,	CMSY10,   VARIABLE,	NULL },
    { "A",		65,	CMSY10,   VARIABLE,	NULL },
    { "B",		66,	CMSY10,   VARIABLE,	NULL },
    { "C",		67,	CMSY10,   VARIABLE,	NULL },
    { "D",		68,	CMSY10,   VARIABLE,	NULL },
    { "E",		69,	CMSY10,   VARIABLE,	NULL },
    { "F",		70,	CMSY10,   VARIABLE,	NULL },
    { "G",		71,	CMSY10,   VARIABLE,	NULL },
    { "H",		72,	CMSY10,   VARIABLE,	NULL },
    { "I",		73,	CMSY10,   VARIABLE,	NULL },
    { "J",		74,	CMSY10,   VARIABLE,	NULL },
    { "K",		75,	CMSY10,   VARIABLE,	NULL },
    { "L",		76,	CMSY10,   VARIABLE,	NULL },
    { "M",		77,	CMSY10,   VARIABLE,	NULL },
    { "N",		78,	CMSY10,   VARIABLE,	NULL },
    { "O",		79,	CMSY10,   VARIABLE,	NULL },
    { "P",		80,	CMSY10,   VARIABLE,	NULL },
    { "Q",		81,	CMSY10,   VARIABLE,	NULL },
    { "R",		82,	CMSY10,   VARIABLE,	NULL },
    { "S",		83,	CMSY10,   VARIABLE,	NULL },
    { "T",		84,	CMSY10,   VARIABLE,	NULL },
    { "U",		85,	CMSY10,   VARIABLE,	NULL },
    { "V",		86,	CMSY10,   VARIABLE,	NULL },
    { "W",		87,	CMSY10,   VARIABLE,	NULL },
    { "X",		88,	CMSY10,   VARIABLE,	NULL },
    { "Y",		89,	CMSY10,   VARIABLE,	NULL },
    { "Z",		90,	CMSY10,   VARIABLE,	NULL },
    /* --- operations and relations --- */
    { "\\cup",		91,	CMSY10,   OPERATOR,	NULL },
    { "\\cap",		92,	CMSY10,   OPERATOR,	NULL },
    { "\\uplus",	93,	CMSY10,   OPERATOR,	NULL },
    { "\\wedge",	94,	CMSY10,   OPERATOR,	NULL },
    { "\\vee",		95,	CMSY10,   OPERATOR,	NULL },
    { "\\vdash",	96,	CMSY10,   RELATION,	NULL },
    { "\\dashv",	97,	CMSY10,   RELATION,	NULL },
    /* --- brackets --- */
    { "\\lfloor",	98,	CMSY10,   OPENING,	NULL },
    { "\\rfloor",	99,	CMSY10,   CLOSING,	NULL },
    { "\\lceil",	100,	CMSY10,   OPENING,	NULL },
    { "\\rceil",	101,	CMSY10,   CLOSING,	NULL },
    { "\\lbrace",	102,	CMSY10,   OPENING,	NULL },
    { "{",		102,	CMSY10,   OPENING,	NULL },
    { "\\{",		102,	CMSY10,   OPENING,	NULL },
    { "\\rbrace",	103,	CMSY10,   CLOSING,	NULL },
    { "}",		103,	CMSY10,   CLOSING,	NULL },
    { "\\}",		103,	CMSY10,   CLOSING,	NULL },
    { "\\langle",	104,	CMSY10,   OPENING,	NULL },
    { "\\rangle",	105,	CMSY10,   CLOSING,	NULL },
    { "\\mid",		106,	CMSY10,   ORDINARY,	NULL },
    { "|",		106,	CMSY10,   BINARYOP,	NULL },
    { "\\parallel",	107,	CMSY10,   BINARYOP,	NULL },
    { "\\|",		107,	CMSY10,   BINARYOP,	NULL },
    /* --- arrows --- */
    { "\\updownarrow",	108,	CMSY10,   ARROW,	NULL },
    { "\\Updownarrow",	109,	CMSY10,   ARROW,	NULL },
    /* --- symbols and operations and relations --- */
    { "\\setminus",	110,	CMSY10,   BINARYOP,	NULL },
    { "\\backslash",	110,	CMSY10,   BINARYOP,	NULL },
    { "\\wr",		111,	CMSY10,   BINARYOP,	NULL },
    { "\\surd",		112,	CMSY10,   OPERATOR,	NULL },
    { "\\amalg",	113,	CMSY10,   BINARYOP,	NULL },
    { "\\nabla",	114,	CMSY10,   VARIABLE,	NULL },
    { "\\smallint",	115,	CMSY10,   OPERATOR,	NULL },
    { "\\sqcup",	116,	CMSY10,   OPERATOR,	NULL },
    { "\\sqcap",	117,	CMSY10,   OPERATOR,	NULL },
    { "\\sqsubseteq",	118,	CMSY10,   RELATION,	NULL },
    { "\\sqsupseteq",	119,	CMSY10,   RELATION,	NULL },
    /* --- special characters --- */
    { "\\S",		120,	CMSY10,   ORDINARY,	NULL },
    { "\\dag",		121,	CMSY10,   ORDINARY,	NULL },
    { "\\dagger",	121,	CMSY10,   ORDINARY,	NULL },
    { "\\ddag",		122,	CMSY10,   ORDINARY,	NULL },
    { "\\ddagger",	122,	CMSY10,   ORDINARY,	NULL },
    { "\\P",		123,	CMSY10,   ORDINARY,	NULL },
    { "\\clubsuit",	124,	CMSY10,   ORDINARY,	NULL },
    { "\\Diamond",	125,	CMSY10,   ORDINARY,	NULL },
    { "\\Heart",	126,	CMSY10,   ORDINARY,	NULL },
    { "\\spadesuit",	127,	CMSY10,   ORDINARY,	NULL },
    /* ---------------------- C M R ---------------------------
          symbol     charnum    family    class	    function
    -------------------------------------------------------- */
    /* --- uppercase greek letters --- */
    { "\\Gamma",	0,	CMR10,   VARIABLE,	NULL },
    { "\\Delta",	1,	CMR10,   VARIABLE,	NULL },
    { "\\Theta",	2,	CMR10,   VARIABLE,	NULL },
    { "\\Lambda",	3,	CMR10,   VARIABLE,	NULL },
    { "\\Xi",		4,	CMR10,   VARIABLE,	NULL },
    { "\\Pi",		5,	CMR10,   VARIABLE,	NULL },
    { "\\Sigma",	6,	CMR10,   VARIABLE,	NULL },
    { "\\smallsum",	6,	CMR10,   OPERATOR,	NULL },
    { "\\Upsilon",	7,	CMR10,   VARIABLE,	NULL },
    { "\\Phi",		8,	CMR10,   VARIABLE,	NULL },
    { "\\Psi",		9,	CMR10,   VARIABLE,	NULL },
    { "\\Omega",	10,	CMR10,   VARIABLE,	NULL },
    /* ---  --- */
    { "\\ff",		11,	CMR10,   ORDINARY,	NULL },
    { "\\fi",		12,	CMR10,   ORDINARY,	NULL },
    { "\\fl",		13,	CMR10,   ORDINARY,	NULL },
    { "\\ffi",		14,	CMR10,   ORDINARY,	NULL },
    { "\\ffl",		15,	CMR10,   ORDINARY,	NULL },
    { "\\imath",	16,	CMR10,   ORDINARY,	NULL },
    { "\\jmath",	17,	CMR10,   ORDINARY,	NULL },
    /* --- foreign letters --- */
    { "\\ss",		25,	CMR10,   ORDINARY,	NULL },
    { "\\ae",		26,	CMR10,   ORDINARY,	NULL },
    { "\\oe",		27,	CMR10,   ORDINARY,	NULL },
    { "\\AE",		29,	CMR10,   ORDINARY,	NULL },
    { "\\OE",		30,	CMR10,   ORDINARY,	NULL },
    /* --- digits 0-9 --- */
    { "0",		48,	CMR10,   ORDINARY,	NULL },
    { "1",		49,	CMR10,   ORDINARY,	NULL },
    { "2",		50,	CMR10,   ORDINARY,	NULL },
    { "3",		51,	CMR10,   ORDINARY,	NULL },
    { "4",		52,	CMR10,   ORDINARY,	NULL },
    { "5",		53,	CMR10,   ORDINARY,	NULL },
    { "6",		54,	CMR10,   ORDINARY,	NULL },
    { "7",		55,	CMR10,   ORDINARY,	NULL },
    { "8",		56,	CMR10,   ORDINARY,	NULL },
    { "9",		57,	CMR10,   ORDINARY,	NULL },
    /* --- symbols, relations, etc --- */
    { "\\gravesym",	18,	CMR10,   ORDINARY,	NULL },
    { "\\acutesym",	19,	CMR10,   ORDINARY,	NULL },
    { "\\checksym",	20,	CMR10,   ORDINARY,	NULL },
    { "\\brevesym",	21,	CMR10,   ORDINARY,	NULL },
    { "!",		33,	CMR10,   BINARYOP,	NULL },
    { "\"",		34,	CMR10,   ORDINARY,	NULL },
    { "\\quote",	34,	CMR10,   ORDINARY,	NULL },
    { "#",		35,	CMR10,   BINARYOP,	NULL },
    { "\\#",		35,	CMR10,   BINARYOP,	NULL },
    { "$",		36,	CMR10,   BINARYOP,	NULL },
    { "\\$",		36,	CMR10,   BINARYOP,	NULL },
    { "%",		37,	CMR10,   BINARYOP,	NULL },
    { "\\%",		37,	CMR10,   BINARYOP,	NULL },
    { "\\percent",	37,	CMR10,   BINARYOP,	NULL },
    { "&",		38,	CMR10,   BINARYOP,	NULL },
    { "\\&",		38,	CMR10,   BINARYOP,	NULL },
    { "\'",		39,	CMR10,   BINARYOP,	NULL },
    { "\\\'",		39,	CMR10,   BINARYOP,	NULL },
    { "\\apostrophe",	39,	CMR10,   ORDINARY,	NULL },
    { "(",		40,	CMR10,   OPENING,	NULL },
    { "\\(",		40,	CMR10,   OPENING,	NULL },
    { ")",		41,	CMR10,   CLOSING,	NULL },
    { "\\)",		41,	CMR10,   CLOSING,	NULL },
    { "*",		42,	CMR10,   BINARYOP,	NULL },
    { "+",		43,	CMR10,   BINARYOP,	NULL },
    { "/",		47,	CMR10,   BINARYOP,	NULL },
    { ":",		58,	CMR10,   ORDINARY,	NULL },
    { ";",		59,	CMR10,   ORDINARY,	NULL },
    { "=",		61,	CMR10,   RELATION,	NULL },
    { "?",		63,	CMR10,   BINARYOP,	NULL },
    { "@",		64,	CMR10,   BINARYOP,	NULL },
    { "[",		91,	CMR10,   OPENING,	NULL },
    { "\\[",		91,	CMR10,   OPENING,	NULL },
    { "]",		93,	CMR10,   CLOSING,	NULL },
    { "\\]",		93,	CMR10,   CLOSING,	NULL },
    { "\\^",		94,	CMR10,   BINARYOP,	NULL },
    { "\\~",		126,	CMR10,   OPERATOR,	NULL },
    /* --- uppercase letters --- */
    { "A",		65,	CMR10,   VARIABLE,	NULL },
    { "B",		66,	CMR10,   VARIABLE,	NULL },
    { "C",		67,	CMR10,   VARIABLE,	NULL },
    { "D",		68,	CMR10,   VARIABLE,	NULL },
    { "E",		69,	CMR10,   VARIABLE,	NULL },
    { "F",		70,	CMR10,   VARIABLE,	NULL },
    { "G",		71,	CMR10,   VARIABLE,	NULL },
    { "H",		72,	CMR10,   VARIABLE,	NULL },
    { "I",		73,	CMR10,   VARIABLE,	NULL },
    { "J",		74,	CMR10,   VARIABLE,	NULL },
    { "K",		75,	CMR10,   VARIABLE,	NULL },
    { "L",		76,	CMR10,   VARIABLE,	NULL },
    { "M",		77,	CMR10,   VARIABLE,	NULL },
    { "N",		78,	CMR10,   VARIABLE,	NULL },
    { "O",		79,	CMR10,   VARIABLE,	NULL },
    { "P",		80,	CMR10,   VARIABLE,	NULL },
    { "Q",		81,	CMR10,   VARIABLE,	NULL },
    { "R",		82,	CMR10,   VARIABLE,	NULL },
    { "S",		83,	CMR10,   VARIABLE,	NULL },
    { "T",		84,	CMR10,   VARIABLE,	NULL },
    { "U",		85,	CMR10,   VARIABLE,	NULL },
    { "V",		86,	CMR10,   VARIABLE,	NULL },
    { "W",		87,	CMR10,   VARIABLE,	NULL },
    { "X",		88,	CMR10,   VARIABLE,	NULL },
    { "Y",		89,	CMR10,   VARIABLE,	NULL },
    { "Z",		90,	CMR10,   VARIABLE,	NULL },
    /* --- lowercase letters --- */
    { "a",		97,	CMR10,   VARIABLE,	NULL },
    { "b",		98,	CMR10,   VARIABLE,	NULL },
    { "c",		99,	CMR10,   VARIABLE,	NULL },
    { "d",		100,	CMR10,   VARIABLE,	NULL },
    { "e",		101,	CMR10,   VARIABLE,	NULL },
    { "f",		102,	CMR10,   VARIABLE,	NULL },
    { "g",		103,	CMR10,   VARIABLE,	NULL },
    { "h",		104,	CMR10,   VARIABLE,	NULL },
    { "i",		105,	CMR10,   VARIABLE,	NULL },
    { "j",		106,	CMR10,   VARIABLE,	NULL },
    { "k",		107,	CMR10,   VARIABLE,	NULL },
    { "l",		108,	CMR10,   VARIABLE,	NULL },
    { "m",		109,	CMR10,   VARIABLE,	NULL },
    { "n",		110,	CMR10,   VARIABLE,	NULL },
    { "o",		111,	CMR10,   VARIABLE,	NULL },
    { "p",		112,	CMR10,   VARIABLE,	NULL },
    { "q",		113,	CMR10,   VARIABLE,	NULL },
    { "r",		114,	CMR10,   VARIABLE,	NULL },
    { "s",		115,	CMR10,   VARIABLE,	NULL },
    { "t",		116,	CMR10,   VARIABLE,	NULL },
    { "u",		117,	CMR10,   VARIABLE,	NULL },
    { "v",		118,	CMR10,   VARIABLE,	NULL },
    { "w",		119,	CMR10,   VARIABLE,	NULL },
    { "x",		120,	CMR10,   VARIABLE,	NULL },
    { "y",		121,	CMR10,   VARIABLE,	NULL },
    { "z",		122,	CMR10,   VARIABLE,	NULL },
    /* --------------------- C M E X --------------------------
          symbol     charnum    family    class	    function
    -------------------------------------------------------- */
    /* --- parens ()'s --- */
    { "\\big(",		0,	CMEX10,   OPENING,	NULL },
    { "\\big)",		1,	CMEX10,   CLOSING,	NULL },
    { "\\Big(",		16,	CMEX10,   OPENING,	NULL },
    { "\\Big)",		17,	CMEX10,   CLOSING,	NULL },
    { "\\bigg(",	18,	CMEX10,   OPENING,	NULL },
    { "\\bigg)",	19,	CMEX10,   CLOSING,	NULL },
    { "\\Bigg(",	32,	CMEX10,   OPENING,	NULL },
    { "\\Bigg)",	33,	CMEX10,   CLOSING,	NULL },
    { "\\bigl(",	0,	CMEX10,   OPENING,	NULL },
    { "\\bigr)",	1,	CMEX10,   CLOSING,	NULL },
    { "\\Bigl(",	16,	CMEX10,   OPENING,	NULL },
    { "\\Bigr)",	17,	CMEX10,   CLOSING,	NULL },
    { "\\biggl(",	18,	CMEX10,   OPENING,	NULL },
    { "\\biggr)",	19,	CMEX10,   CLOSING,	NULL },
    { "\\Biggl(",	32,	CMEX10,   OPENING,	NULL },
    { "\\Biggr)",	33,	CMEX10,   CLOSING,	NULL },
    /* --- brackets []'s --- */
    { "\\big[",		2,	CMEX10,   OPENING,	NULL },
    { "\\big]",		3,	CMEX10,   CLOSING,	NULL },
    { "\\bigg[",	20,	CMEX10,   OPENING,	NULL },
    { "\\bigg]",	21,	CMEX10,   CLOSING,	NULL },
    { "\\Bigg[",	34,	CMEX10,   OPENING,	NULL },
    { "\\Bigg]",	35,	CMEX10,   CLOSING,	NULL },
    { "\\Big[",		104,	CMEX10,   OPENING,	NULL },
    { "\\Big]",		105,	CMEX10,   CLOSING,	NULL },
    { "\\bigl[",	2,	CMEX10,   OPENING,	NULL },
    { "\\bigr]",	3,	CMEX10,   CLOSING,	NULL },
    { "\\biggl[",	20,	CMEX10,   OPENING,	NULL },
    { "\\biggr]",	21,	CMEX10,   CLOSING,	NULL },
    { "\\Biggl[",	34,	CMEX10,   OPENING,	NULL },
    { "\\Biggr]",	35,	CMEX10,   CLOSING,	NULL },
    { "\\Bigl[",	104,	CMEX10,   OPENING,	NULL },
    { "\\Bigr]",	105,	CMEX10,   CLOSING,	NULL },
    /* --- braces {}'s --- */
    { "\\big{",		8,	CMEX10,   OPENING,	NULL },
    { "\\big}",		9,	CMEX10,   CLOSING,	NULL },
    { "\\bigg{",	26,	CMEX10,   OPENING,	NULL },
    { "\\bigg}",	27,	CMEX10,   CLOSING,	NULL },
    { "\\Bigg{",	40,	CMEX10,   OPENING,	NULL },
    { "\\Bigg}",	41,	CMEX10,   CLOSING,	NULL },
    { "\\Big{",		110,	CMEX10,   OPENING,	NULL },
    { "\\Big}",		111,	CMEX10,   CLOSING,	NULL },
    { "\\bigl{",	8,	CMEX10,   OPENING,	NULL },
    { "\\bigr}",	9,	CMEX10,   CLOSING,	NULL },
    { "\\biggl{",	26,	CMEX10,   OPENING,	NULL },
    { "\\biggr}",	27,	CMEX10,   CLOSING,	NULL },
    { "\\Biggl{",	40,	CMEX10,   OPENING,	NULL },
    { "\\Biggr}",	41,	CMEX10,   CLOSING,	NULL },
    { "\\Bigl{",	110,	CMEX10,   OPENING,	NULL },
    { "\\Bigr}",	111,	CMEX10,   CLOSING,	NULL },
    { "\\big\\{",	8,	CMEX10,   OPENING,	NULL },
    { "\\big\\}",	9,	CMEX10,   CLOSING,	NULL },
    { "\\bigg\\{",	26,	CMEX10,   OPENING,	NULL },
    { "\\bigg\\}",	27,	CMEX10,   CLOSING,	NULL },
    { "\\Bigg\\{",	40,	CMEX10,   OPENING,	NULL },
    { "\\Bigg\\}",	41,	CMEX10,   CLOSING,	NULL },
    { "\\Big\\{",	110,	CMEX10,   OPENING,	NULL },
    { "\\Big\\}",	111,	CMEX10,   CLOSING,	NULL },
    { "\\bigl\\{",	8,	CMEX10,   OPENING,	NULL },
    { "\\bigr\\}",	9,	CMEX10,   CLOSING,	NULL },
    { "\\biggl\\{",	26,	CMEX10,   OPENING,	NULL },
    { "\\biggr\\}",	27,	CMEX10,   CLOSING,	NULL },
    { "\\Biggl\\{",	40,	CMEX10,   OPENING,	NULL },
    { "\\Biggr\\}",	41,	CMEX10,   CLOSING,	NULL },
    { "\\Bigl\\{",	110,	CMEX10,   OPENING,	NULL },
    { "\\Bigr\\}",	111,	CMEX10,   CLOSING,	NULL },
    { "\\big\\lbrace",	8,	CMEX10,   OPENING,	NULL },
    { "\\big\\rbrace",	9,	CMEX10,   CLOSING,	NULL },
    { "\\bigg\\lbrace",	26,	CMEX10,   OPENING,	NULL },
    { "\\bigg\\rbrace",	27,	CMEX10,   CLOSING,	NULL },
    { "\\Bigg\\lbrace",	40,	CMEX10,   OPENING,	NULL },
    { "\\Bigg\\rbrace",	41,	CMEX10,   CLOSING,	NULL },
    { "\\Big\\lbrace",	110,	CMEX10,   OPENING,	NULL },
    { "\\Big\\rbrace",	111,	CMEX10,   CLOSING,	NULL },
    /* --- angles <>'s --- */
    { "\\big<",		10,	CMEX10,   OPENING,	NULL },
    { "\\big>",		11,	CMEX10,   CLOSING,	NULL },
    { "\\bigg<",	28,	CMEX10,   OPENING,	NULL },
    { "\\bigg>",	29,	CMEX10,   CLOSING,	NULL },
    { "\\Bigg<",	42,	CMEX10,   OPENING,	NULL },
    { "\\Bigg>",	43,	CMEX10,   CLOSING,	NULL },
    { "\\Big<",		68,	CMEX10,   OPENING,	NULL },
    { "\\Big>",		69,	CMEX10,   CLOSING,	NULL },
    { "\\bigl<",	10,	CMEX10,   OPENING,	NULL },
    { "\\bigr>",	11,	CMEX10,   CLOSING,	NULL },
    { "\\biggl<",	28,	CMEX10,   OPENING,	NULL },
    { "\\biggr>",	29,	CMEX10,   CLOSING,	NULL },
    { "\\Biggl<",	42,	CMEX10,   OPENING,	NULL },
    { "\\Biggr>",	43,	CMEX10,   CLOSING,	NULL },
    { "\\Bigl<",	68,	CMEX10,   OPENING,	NULL },
    { "\\Bigr>",	69,	CMEX10,   CLOSING,	NULL },
    { "\\big\\langle",	10,	CMEX10,   OPENING,	NULL },
    { "\\big\\rangle",	11,	CMEX10,   CLOSING,	NULL },
    { "\\bigg\\langle",	28,	CMEX10,   OPENING,	NULL },
    { "\\bigg\\rangle",	29,	CMEX10,   CLOSING,	NULL },
    { "\\Bigg\\langle",	42,	CMEX10,   OPENING,	NULL },
    { "\\Bigg\\rangle",	43,	CMEX10,   CLOSING,	NULL },
    { "\\Big\\langle",	68,	CMEX10,   OPENING,	NULL },
    { "\\Big\\rangle",	69,	CMEX10,   CLOSING,	NULL },
    /* --- hats ^ --- */
    { "^",		98,	CMEX10,   OPERATOR,	NULL },
    { "^",		99,	CMEX10,   OPERATOR,	NULL },
    { "^",		100,	CMEX10,   OPERATOR,	NULL },
    /* --- tildes --- */
    { "~",		101,	CMEX10,   OPERATOR,	NULL },
    { "~",		102,	CMEX10,   OPERATOR,	NULL },
    { "~",		103,	CMEX10,   OPERATOR,	NULL },
    /* --- /'s --- */
    { "/",		44,	CMEX10,   OPENING,	NULL },
    { "/",		46,	CMEX10,   OPENING,	NULL },
    { "\\",		45,	CMEX10,   OPENING,	NULL },
    { "\\",		47,	CMEX10,   OPENING,	NULL },
    /* --- \sum, \int and other (displaymath) symbols --- */
    { "\\bigsqcup",	70,	CMEX10,   LOWERBIG,	NULL },
    { "\\Bigsqcup",	71,	CMEX10,   UPPERBIG,	NULL },
    { "\\oint",		72,	CMEX10,   OPERATOR,	NULL },
    { "\\bigoint",	72,	CMEX10,   LOWERBIG,	NULL },
    { "\\Bigoint",	73,	CMEX10,   UPPERBIG,	NULL },
    { "\\bigodot",	74,	CMEX10,   LOWERBIG,	NULL },
    { "\\Bigodot",	75,	CMEX10,   UPPERBIG,	NULL },
    { "\\bigoplus",	76,	CMEX10,   LOWERBIG,	NULL },
    { "\\Bigoplus",	77,	CMEX10,   UPPERBIG,	NULL },
    { "\\bigotimes",	78,	CMEX10,   LOWERBIG,	NULL },
    { "\\Bigotimes",	79,	CMEX10,   UPPERBIG,	NULL },
    { "\\sum",		80,	CMEX10,   OPERATOR,	NULL },
    { "\\bigsum",	80,	CMEX10,   LOWERBIG,	NULL },
    { "\\prod",		81,	CMEX10,   OPERATOR,	NULL },
    { "\\bigprod",	81,	CMEX10,   LOWERBIG,	NULL },
    { "\\int",		82,	CMEX10,   OPERATOR,	NULL },
    { "\\bigint",	82,	CMEX10,   LOWERBIG,	NULL },
    { "\\bigcup",	83,	CMEX10,   LOWERBIG,	NULL },
    { "\\bigcap",	84,	CMEX10,   LOWERBIG,	NULL },
    { "\\biguplus",	85,	CMEX10,   LOWERBIG,	NULL },
    { "\\bigwedge",	86,	CMEX10,   LOWERBIG,	NULL },
    { "\\bigvee",	87,	CMEX10,   LOWERBIG,	NULL },
    { "\\Bigsum",	88,	CMEX10,   UPPERBIG,	NULL },
    { "\\big\\sum",	88,	CMEX10,   UPPERBIG,	NULL },
    { "\\Big\\sum",	88,	CMEX10,   UPPERBIG,	NULL },
    { "\\bigg\\sum",	88,	CMEX10,   UPPERBIG,	NULL },
    { "\\Bigg\\sum",	88,	CMEX10,   UPPERBIG,	NULL },
    { "\\Bigprod",	89,	CMEX10,   UPPERBIG,	NULL },
    { "\\Bigint",	90,	CMEX10,   UPPERBIG,	NULL },
    { "\\big\\int",	90,	CMEX10,   UPPERBIG,	NULL },
    { "\\Big\\int",	90,	CMEX10,   UPPERBIG,	NULL },
    { "\\bigg\\int",	90,	CMEX10,   UPPERBIG,	NULL },
    { "\\Bigg\\int",	90,	CMEX10,   UPPERBIG,	NULL },
    { "\\Bigcup",	91,	CMEX10,   UPPERBIG,	NULL },
    { "\\Bigcap",	92,	CMEX10,   UPPERBIG,	NULL },
    { "\\Biguplus",	93,	CMEX10,   UPPERBIG,	NULL },
    { "\\Bigwedge",	94,	CMEX10,   UPPERBIG,	NULL },
    { "\\Bigvee",	95,	CMEX10,   UPPERBIG,	NULL },
    { "\\coprod",	96,	CMEX10,   LOWERBIG,	NULL },
    { "\\bigcoprod",	96,	CMEX10,   LOWERBIG,	NULL },
    { "\\Bigcoprod",	97,	CMEX10,   UPPERBIG,	NULL },
    /* --- symbol pieces (see TeXbook page 432) --- */
    { "\\leftbracetop",	56,	CMEX10,   OPENING,	NULL },
    { "\\rightbracetop",57,	CMEX10,   CLOSING,	NULL },
    { "\\leftbracebot",	58,	CMEX10,   OPENING,	NULL },
    { "\\rightbracebot",59,	CMEX10,   CLOSING,	NULL },
    { "\\leftbracemid",	60,	CMEX10,   OPENING,	NULL },
    { "\\rightbracemid",61,	CMEX10,   CLOSING,	NULL },
    { "\\leftbracebar",	62,	CMEX10,   OPENING,	NULL },
    { "\\rightbracebar",62,	CMEX10,   CLOSING,	NULL },
    { "\\leftparentop",	48,	CMEX10,   OPENING,	NULL },
    { "\\rightparentop",49,	CMEX10,   CLOSING,	NULL },
    { "\\leftparenbot",	64,	CMEX10,   OPENING,	NULL },
    { "\\rightparenbot",65,	CMEX10,   CLOSING,	NULL },
    { "\\leftparenbar",	66,	CMEX10,   OPENING,	NULL },
    { "\\rightparenbar",67,	CMEX10,   CLOSING,	NULL },
    /* --------------------- R S F S --------------------------
          symbol     charnum    family    class	    function
    -------------------------------------------------------- */
    /* --- rsfs script letters (written as \scr{A...Z}) --- */
    { "A",		 0,	RSFS10,   VARIABLE,	NULL },
    { "B",		 1,	RSFS10,   VARIABLE,	NULL },
    { "C",		 2,	RSFS10,   VARIABLE,	NULL },
    { "D",		 3,	RSFS10,   VARIABLE,	NULL },
    { "E",		 4,	RSFS10,   VARIABLE,	NULL },
    { "F",		 5,	RSFS10,   VARIABLE,	NULL },
    { "G",		 6,	RSFS10,   VARIABLE,	NULL },
    { "H",		 7,	RSFS10,   VARIABLE,	NULL },
    { "I",		 8,	RSFS10,   VARIABLE,	NULL },
    { "J",		 9,	RSFS10,   VARIABLE,	NULL },
    { "K",		10,	RSFS10,   VARIABLE,	NULL },
    { "L",		11,	RSFS10,   VARIABLE,	NULL },
    { "M",		12,	RSFS10,   VARIABLE,	NULL },
    { "N",		13,	RSFS10,   VARIABLE,	NULL },
    { "O",		14,	RSFS10,   VARIABLE,	NULL },
    { "P",		15,	RSFS10,   VARIABLE,	NULL },
    { "Q",		16,	RSFS10,   VARIABLE,	NULL },
    { "R",		17,	RSFS10,   VARIABLE,	NULL },
    { "S",		18,	RSFS10,   VARIABLE,	NULL },
    { "T",		19,	RSFS10,   VARIABLE,	NULL },
    { "U",		20,	RSFS10,   VARIABLE,	NULL },
    { "V",		21,	RSFS10,   VARIABLE,	NULL },
    { "W",		22,	RSFS10,   VARIABLE,	NULL },
    { "X",		23,	RSFS10,   VARIABLE,	NULL },
    { "Y",		24,	RSFS10,   VARIABLE,	NULL },
    { "Z",		25,	RSFS10,   VARIABLE,	NULL },
    /* --- rsfs script letters (written as \scrA...\scrZ) --- */
    { "\\scrA",		 0,	RSFS10,   VARIABLE,	NULL },
    { "\\scrB",		 1,	RSFS10,   VARIABLE,	NULL },
    { "\\scrC",		 2,	RSFS10,   VARIABLE,	NULL },
    { "\\scrD",		 3,	RSFS10,   VARIABLE,	NULL },
    { "\\scrE",		 4,	RSFS10,   VARIABLE,	NULL },
    { "\\scrF",		 5,	RSFS10,   VARIABLE,	NULL },
    { "\\scrG",		 6,	RSFS10,   VARIABLE,	NULL },
    { "\\scrH",		 7,	RSFS10,   VARIABLE,	NULL },
    { "\\scrI",		 8,	RSFS10,   VARIABLE,	NULL },
    { "\\scrJ",		 9,	RSFS10,   VARIABLE,	NULL },
    { "\\scrK",		10,	RSFS10,   VARIABLE,	NULL },
    { "\\scrL",		11,	RSFS10,   VARIABLE,	NULL },
    { "\\scrM",		12,	RSFS10,   VARIABLE,	NULL },
    { "\\scrN",		13,	RSFS10,   VARIABLE,	NULL },
    { "\\scrO",		14,	RSFS10,   VARIABLE,	NULL },
    { "\\scrP",		15,	RSFS10,   VARIABLE,	NULL },
    { "\\scrQ",		16,	RSFS10,   VARIABLE,	NULL },
    { "\\scrR",		17,	RSFS10,   VARIABLE,	NULL },
    { "\\scrS",		18,	RSFS10,   VARIABLE,	NULL },
    { "\\scrT",		19,	RSFS10,   VARIABLE,	NULL },
    { "\\scrU",		20,	RSFS10,   VARIABLE,	NULL },
    { "\\scrV",		21,	RSFS10,   VARIABLE,	NULL },
    { "\\scrW",		22,	RSFS10,   VARIABLE,	NULL },
    { "\\scrX",		23,	RSFS10,   VARIABLE,	NULL },
    { "\\scrY",		24,	RSFS10,   VARIABLE,	NULL },
    { "\\scrZ",		25,	RSFS10,   VARIABLE,	NULL },
    /* -------------------- B B O L D -------------------------
          symbol     charnum    family    class	    function
    -------------------------------------------------------- */
    /* --- uppercase greek letters --- */
    { "\\Gamma",	0,     BBOLD10,   VARIABLE,	NULL },
    { "\\Delta",	1,     BBOLD10,   VARIABLE,	NULL },
    { "\\Theta",	2,     BBOLD10,   VARIABLE,	NULL },
    { "\\Lambda",	3,     BBOLD10,   VARIABLE,	NULL },
    { "\\Xi",		4,     BBOLD10,   VARIABLE,	NULL },
    { "\\Pi",		5,     BBOLD10,   VARIABLE,	NULL },
    { "\\Sigma",	6,     BBOLD10,   VARIABLE,	NULL },
    { "\\smallsum",	6,     BBOLD10,   OPERATOR,	NULL },
    { "\\Upsilon",	7,     BBOLD10,   VARIABLE,	NULL },
    { "\\Phi",		8,     BBOLD10,   VARIABLE,	NULL },
    { "\\Psi",		9,     BBOLD10,   VARIABLE,	NULL },
    { "\\Omega",	10,    BBOLD10,   VARIABLE,	NULL },
    /* --- lowercase greek letters --- */
    { "\\alpha",	11,    BBOLD10,   VARIABLE,	NULL },
    { "\\beta",		12,    BBOLD10,   VARIABLE,	NULL },
    { "\\gamma",	13,    BBOLD10,   VARIABLE,	NULL },
    { "\\delta",	14,    BBOLD10,   VARIABLE,	NULL },
    { "\\epsilon",	15,    BBOLD10,   VARIABLE,	NULL },
    { "\\zeta",		16,    BBOLD10,   VARIABLE,	NULL },
    { "\\eta",		17,    BBOLD10,   VARIABLE,	NULL },
    { "\\theta",	18,    BBOLD10,   VARIABLE,	NULL },
    { "\\iota",		19,    BBOLD10,   VARIABLE,	NULL },
    { "\\kappa",	20,    BBOLD10,   VARIABLE,	NULL },
    { "\\lambda",	21,    BBOLD10,   VARIABLE,	NULL },
    { "\\mu",		22,    BBOLD10,   VARIABLE,	NULL },
    { "\\nu",		23,    BBOLD10,   VARIABLE,	NULL },
    { "\\xi",		24,    BBOLD10,   VARIABLE,	NULL },
    { "\\pi",		25,    BBOLD10,   VARIABLE,	NULL },
    { "\\rho",		26,    BBOLD10,   VARIABLE,	NULL },
    { "\\sigma",	27,    BBOLD10,   VARIABLE,	NULL },
    { "\\tau",		28,    BBOLD10,   VARIABLE,	NULL },
    { "\\upsilon",	29,    BBOLD10,   VARIABLE,	NULL },
    { "\\phi",		30,    BBOLD10,   VARIABLE,	NULL },
    { "\\chi",		31,    BBOLD10,   VARIABLE,	NULL },
    { "\\psi",		32,    BBOLD10,   VARIABLE,	NULL },
    { "\\omega",	127,   BBOLD10,   VARIABLE,	NULL },
    /* --- digits 0-9 --- */
    { "0",		48,    BBOLD10,   ORDINARY,	NULL },
    { "1",		49,    BBOLD10,   ORDINARY,	NULL },
    { "2",		50,    BBOLD10,   ORDINARY,	NULL },
    { "3",		51,    BBOLD10,   ORDINARY,	NULL },
    { "4",		52,    BBOLD10,   ORDINARY,	NULL },
    { "5",		53,    BBOLD10,   ORDINARY,	NULL },
    { "6",		54,    BBOLD10,   ORDINARY,	NULL },
    { "7",		55,    BBOLD10,   ORDINARY,	NULL },
    { "8",		56,    BBOLD10,   ORDINARY,	NULL },
    { "9",		57,    BBOLD10,   ORDINARY,	NULL },
    { "\\0",		48,    BBOLD10,   ORDINARY,	NULL },
    { "\\1",		49,    BBOLD10,   ORDINARY,	NULL },
    { "\\2",		50,    BBOLD10,   ORDINARY,	NULL },
    { "\\3",		51,    BBOLD10,   ORDINARY,	NULL },
    { "\\4",		52,    BBOLD10,   ORDINARY,	NULL },
    { "\\5",		53,    BBOLD10,   ORDINARY,	NULL },
    { "\\6",		54,    BBOLD10,   ORDINARY,	NULL },
    { "\\7",		55,    BBOLD10,   ORDINARY,	NULL },
    { "\\8",		56,    BBOLD10,   ORDINARY,	NULL },
    { "\\9",		57,    BBOLD10,   ORDINARY,	NULL },
    /* --- uppercase letters --- */
    { "A",		65,    BBOLD10,   VARIABLE,	NULL },
    { "B",		66,    BBOLD10,   VARIABLE,	NULL },
    { "C",		67,    BBOLD10,   VARIABLE,	NULL },
    { "D",		68,    BBOLD10,   VARIABLE,	NULL },
    { "E",		69,    BBOLD10,   VARIABLE,	NULL },
    { "F",		70,    BBOLD10,   VARIABLE,	NULL },
    { "G",		71,    BBOLD10,   VARIABLE,	NULL },
    { "H",		72,    BBOLD10,   VARIABLE,	NULL },
    { "I",		73,    BBOLD10,   VARIABLE,	NULL },
    { "J",		74,    BBOLD10,   VARIABLE,	NULL },
    { "K",		75,    BBOLD10,   VARIABLE,	NULL },
    { "L",		76,    BBOLD10,   VARIABLE,	NULL },
    { "M",		77,    BBOLD10,   VARIABLE,	NULL },
    { "N",		78,    BBOLD10,   VARIABLE,	NULL },
    { "O",		79,    BBOLD10,   VARIABLE,	NULL },
    { "P",		80,    BBOLD10,   VARIABLE,	NULL },
    { "Q",		81,    BBOLD10,   VARIABLE,	NULL },
    { "R",		82,    BBOLD10,   VARIABLE,	NULL },
    { "S",		83,    BBOLD10,   VARIABLE,	NULL },
    { "T",		84,    BBOLD10,   VARIABLE,	NULL },
    { "U",		85,    BBOLD10,   VARIABLE,	NULL },
    { "V",		86,    BBOLD10,   VARIABLE,	NULL },
    { "W",		87,    BBOLD10,   VARIABLE,	NULL },
    { "X",		88,    BBOLD10,   VARIABLE,	NULL },
    { "Y",		89,    BBOLD10,   VARIABLE,	NULL },
    { "Z",		90,    BBOLD10,   VARIABLE,	NULL },
    /* --- lowercase letters --- */
    { "a",		97,    BBOLD10,   VARIABLE,	NULL },
    { "b",		98,    BBOLD10,   VARIABLE,	NULL },
    { "c",		99,    BBOLD10,   VARIABLE,	NULL },
    { "d",		100,   BBOLD10,   VARIABLE,	NULL },
    { "e",		101,   BBOLD10,   VARIABLE,	NULL },
    { "f",		102,   BBOLD10,   VARIABLE,	NULL },
    { "g",		103,   BBOLD10,   VARIABLE,	NULL },
    { "h",		104,   BBOLD10,   VARIABLE,	NULL },
    { "i",		105,   BBOLD10,   VARIABLE,	NULL },
    { "j",		106,   BBOLD10,   VARIABLE,	NULL },
    { "k",		107,   BBOLD10,   VARIABLE,	NULL },
    { "l",		108,   BBOLD10,   VARIABLE,	NULL },
    { "m",		109,   BBOLD10,   VARIABLE,	NULL },
    { "n",		110,   BBOLD10,   VARIABLE,	NULL },
    { "o",		111,   BBOLD10,   VARIABLE,	NULL },
    { "p",		112,   BBOLD10,   VARIABLE,	NULL },
    { "q",		113,   BBOLD10,   VARIABLE,	NULL },
    { "r",		114,   BBOLD10,   VARIABLE,	NULL },
    { "s",		115,   BBOLD10,   VARIABLE,	NULL },
    { "t",		116,   BBOLD10,   VARIABLE,	NULL },
    { "u",		117,   BBOLD10,   VARIABLE,	NULL },
    { "v",		118,   BBOLD10,   VARIABLE,	NULL },
    { "w",		119,   BBOLD10,   VARIABLE,	NULL },
    { "x",		120,   BBOLD10,   VARIABLE,	NULL },
    { "y",		121,   BBOLD10,   VARIABLE,	NULL },
    { "z",		122,   BBOLD10,   VARIABLE,	NULL },
    /* --- symbols, relations, etc --- */
    { "!",		33,    BBOLD10,   BINARYOP,	NULL },
    { "#",		35,    BBOLD10,   BINARYOP,	NULL },
    { "\\#",		35,    BBOLD10,   BINARYOP,	NULL },
    { "$",		36,    BBOLD10,   BINARYOP,	NULL },
    { "\\$",		36,    BBOLD10,   BINARYOP,	NULL },
    { "%",		37,    BBOLD10,   BINARYOP,	NULL },
    { "\\%",		37,    BBOLD10,   BINARYOP,	NULL },
    { "\\percent",	37,    BBOLD10,   BINARYOP,	NULL },
    { "&",		38,    BBOLD10,   BINARYOP,	NULL },
    { "\\&",		38,    BBOLD10,   BINARYOP,	NULL },
    { "\'",		39,    BBOLD10,   BINARYOP,	NULL },
    { "\\apostrophe",	39,    BBOLD10,   ORDINARY,	NULL },
    { "(",		40,    BBOLD10,   OPENING,	NULL },
    { "\\(",		40,    BBOLD10,   OPENING,	NULL },
    { ")",		41,    BBOLD10,   CLOSING,	NULL },
    { "\\)",		41,    BBOLD10,   CLOSING,	NULL },
    { "*",		42,    BBOLD10,   BINARYOP,	NULL },
    { "+",		43,    BBOLD10,   BINARYOP,	NULL },
    { ",",		44,    BBOLD10,   PUNCTION,	NULL },
    { "-",		45,    BBOLD10,   BINARYOP,	NULL },
    { ".",		46,    BBOLD10,   PUNCTION,	NULL },
    { "/",		47,    BBOLD10,   BINARYOP,	NULL },
    { ":",		58,    BBOLD10,   ORDINARY,	NULL },
    { ";",		59,    BBOLD10,   ORDINARY,	NULL },
    { "<",		60,    BBOLD10,   RELATION,	NULL },
    { "\\<",		60,    BBOLD10,   RELATION,	NULL },
    { "\\cdot",		61,    BBOLD10,   BINARYOP,	NULL },
    { ">",		62,    BBOLD10,   RELATION,	NULL },
    { "\\>",		62,    BBOLD10,   RELATION,	NULL },
    { "?",		63,    BBOLD10,   BINARYOP,	NULL },
    { "@",		64,    BBOLD10,   BINARYOP,	NULL },
    { "[",		91,    BBOLD10,   OPENING,	NULL },
    { "\\[",		91,    BBOLD10,   OPENING,	NULL },
    { "\\\\",		92,    BBOLD10,   OPENING,	NULL },
    { "\\backslash",	92,    BBOLD10,   OPENING,	NULL },
    { "]",		93,    BBOLD10,   CLOSING,	NULL },
    { "\\]",		93,    BBOLD10,   CLOSING,	NULL },
    { "|",		124,   BBOLD10,   BINARYOP,	NULL },
    { "\\-",		123,   BBOLD10,   BINARYOP,	NULL },
    /* ------------------- S T M A R Y ------------------------
          symbol     charnum    family    class	    function
    -------------------------------------------------------- */
    /* --- stmaryrd symbols (see stmaryrd.sty for defs) --- */
    { "\\shortleftarrow",   0, STMARY10,  ARROW,	NULL },
    { "\\shortrightarrow",  1, STMARY10,  ARROW,	NULL },
    { "\\shortuparrow",     2, STMARY10,  ARROW,	NULL },
    { "\\shortdownarrow",   3, STMARY10,  ARROW,	NULL },
    { "\\Yup",		    4, STMARY10,  BINARYOP,	NULL },
    { "\\Ydown",	    5, STMARY10,  BINARYOP,	NULL },
    { "\\Yleft",	    6, STMARY10,  BINARYOP,	NULL },
    { "\\Yright",	    7, STMARY10,  BINARYOP,	NULL },
    { "\\varcurlyvee",	    8, STMARY10,  BINARYOP,	NULL },
    { "\\varcurlywedge",    9, STMARY10,  BINARYOP,	NULL },
    { "\\minuso",	   10, STMARY10,  BINARYOP,	NULL },
    { "\\baro",		   11, STMARY10,  BINARYOP,	NULL },
    { "\\sslash",	   12, STMARY10,  BINARYOP,	NULL },
    { "\\bblash",	   13, STMARY10,  BINARYOP,	NULL },
    { "\\moo",		   14, STMARY10,  BINARYOP,	NULL },
    { "\\varotimes",	   15, STMARY10,  BINARYOP,	NULL },
    { "\\varoast",	   16, STMARY10,  BINARYOP,	NULL },
    { "\\varobar",	   17, STMARY10,  BINARYOP,	NULL },
    { "\\varodot",	   18, STMARY10,  BINARYOP,	NULL },
    { "\\varoslash",	   19, STMARY10,  BINARYOP,	NULL },
    { "\\varobslash",	   20, STMARY10,  BINARYOP,	NULL },
    { "\\varocircle",	   21, STMARY10,  BINARYOP,	NULL },
    { "\\varoplus",	   22, STMARY10,  BINARYOP,	NULL },
    { "\\varominus",	   23, STMARY10,  BINARYOP,	NULL },
    { "\\boxast",	   24, STMARY10,  BINARYOP,	NULL },
    { "\\boxbar",	   25, STMARY10,  BINARYOP,	NULL },
    { "\\boxdot",	   26, STMARY10,  BINARYOP,	NULL },
    { "\\boxslash",	   27, STMARY10,  BINARYOP,	NULL },
    { "\\boxbslash",	   28, STMARY10,  BINARYOP,	NULL },
    { "\\boxcircle",	   29, STMARY10,  BINARYOP,	NULL },
    { "\\boxbox",	   30, STMARY10,  BINARYOP,	NULL },
    { "\\boxempty",	   31, STMARY10,  BINARYOP,	NULL },
    { "\\qed",		   31, STMARY10,  BINARYOP,	NULL },
    { "\\lightning",	   32, STMARY10,  ORDINARY,	NULL },
    { "\\merge",	   33, STMARY10,  BINARYOP,	NULL },
    { "\\vartimes",	   34, STMARY10,  BINARYOP,	NULL },
    { "\\fatsemi",	   35, STMARY10,  BINARYOP,	NULL },
    { "\\sswarrow",	   36, STMARY10,  ARROW,	NULL },
    { "\\ssearrow",	   37, STMARY10,  ARROW,	NULL },
    { "\\curlywedgeuparrow",38,STMARY10,  ARROW,	NULL },
    { "\\curlywedgedownarrow",39,STMARY10,ARROW,	NULL },
    { "\\fatslash",	   40, STMARY10,  BINARYOP,	NULL },
    { "\\fatbslash",	   41, STMARY10,  BINARYOP,	NULL },
    { "\\lbag",		   42, STMARY10,  BINARYOP,	NULL },
    { "\\rbag",		   43, STMARY10,  BINARYOP,	NULL },
    { "\\varbigcirc",	   44, STMARY10,  BINARYOP,	NULL },
    { "\\leftrightarroweq",45, STMARY10,  ARROW,	NULL },
    { "\\curlyveedownarrow",46,STMARY10,  ARROW,	NULL },
    { "\\curlyveeuparrow", 47, STMARY10,  ARROW,	NULL },
    { "\\nnwarrow",	   48, STMARY10,  ARROW,	NULL },
    { "\\nnearrow",	   49, STMARY10,  ARROW,	NULL },
    { "\\leftslice",	   50, STMARY10,  BINARYOP,	NULL },
    { "\\rightslice",	   51, STMARY10,  BINARYOP,	NULL },
    { "\\varolessthan",	   52, STMARY10,  BINARYOP,	NULL },
    { "\\varogreaterthan", 53, STMARY10,  BINARYOP,	NULL },
    { "\\varovee",	   54, STMARY10,  BINARYOP,	NULL },
    { "\\varowedge",	   55, STMARY10,  BINARYOP,	NULL },
    { "\\talloblong",	   56, STMARY10,  BINARYOP,	NULL },
    { "\\interleave",	   57, STMARY10,  BINARYOP,	NULL },
    { "\\obar",		   58, STMARY10,  BINARYOP,	NULL },
    { "\\oslash",	   59, STMARY10,  BINARYOP,	NULL },
    { "\\olessthan",	   60, STMARY10,  BINARYOP,	NULL },
    { "\\ogreaterthan",	   61, STMARY10,  BINARYOP,	NULL },
    { "\\ovee",		   62, STMARY10,  BINARYOP,	NULL },
    { "\\owedge",	   63, STMARY10,  BINARYOP,	NULL },
    { "\\oblong",	   64, STMARY10,  BINARYOP,	NULL },
    { "\\inplus",	   65, STMARY10,  RELATION,	NULL },
    { "\\niplus",	   66, STMARY10,  RELATION,	NULL },
    { "\\nplus",	   67, STMARY10,  BINARYOP,	NULL },
    { "\\subsetplus",	   68, STMARY10,  RELATION,	NULL },
    { "\\supsetplus",	   69, STMARY10,  RELATION,	NULL },
    { "\\subsetpluseq",	   70, STMARY10,  RELATION,	NULL },
    { "\\supsetpluseq",	   71, STMARY10,  RELATION,	NULL },
    { "\\Lbag",		   72, STMARY10,  OPENING,	NULL },
    { "\\Rbag",		   73, STMARY10,  CLOSING,	NULL },
    { "\\llbracket",	   74, STMARY10,  OPENING,	NULL },
    { "\\rrbracket",	   75, STMARY10,  CLOSING,	NULL },
    { "\\llparenthesis",   76, STMARY10,  OPENING,	NULL },
    { "\\rrparenthesis",   77, STMARY10,  CLOSING,	NULL },
    { "\\binampersand",	   78, STMARY10,  OPENING,	NULL },
    { "\\bindnasrepma",	   79, STMARY10,  CLOSING,	NULL },
    { "\\trianglelefteqslant",80,STMARY10,RELATION,	NULL },
    { "\\trianglerighteqslant",81,STMARY10,RELATION,	NULL },
    { "\\ntrianglelefteqslant",82,STMARY10,RELATION,	NULL },
    { "\\ntrianglerighteqslant",83,STMARY10,RELATION,	NULL },
    { "\\llfloor",	   84, STMARY10,  OPENING,	NULL },
    { "\\rrfloor",	   85, STMARY10,  CLOSING,	NULL },
    { "\\llceil",	   86, STMARY10,  OPENING,	NULL },
    { "\\rrceil",	   87, STMARY10,  CLOSING,	NULL },
    { "\\arrownot",	   88, STMARY10,  RELATION,	NULL },
    { "\\Arrownot",	   89, STMARY10,  RELATION,	NULL },
    { "\\Mapstochar",	   90, STMARY10,  RELATION,	NULL },
    { "\\mapsfromchar",	   91, STMARY10,  RELATION,	NULL },
    { "\\Mapsfromchar",	   92, STMARY10,  RELATION,	NULL },
    { "\\leftrightarrowtriangle",93,STMARY10,BINARYOP,	NULL },
    { "\\leftarrowtriangle",94,STMARY10,  RELATION,	NULL },
    { "\\rightarrowtriangle",95,STMARY10, RELATION,	NULL },
    { "\\bigtriangledown", 96, STMARY10,  OPERATOR,	NULL },
    { "\\bigtriangleup",   97, STMARY10,  OPERATOR,	NULL },
    { "\\bigcurlyvee",	   98, STMARY10,  OPERATOR,	NULL },
    { "\\bigcurlywedge",   99, STMARY10,  OPERATOR,	NULL },
    { "\\bigsqcap",	  100, STMARY10,  OPERATOR,	NULL },
    { "\\Bigsqcap",	  100, STMARY10,  OPERATOR,	NULL },
    { "\\bigbox",	  101, STMARY10,  OPERATOR,	NULL },
    { "\\bigparallel",	  102, STMARY10,  OPERATOR,	NULL },
    { "\\biginterleave",  103, STMARY10,  OPERATOR,	NULL },
    { "\\bignplus",	  112, STMARY10,  OPERATOR,	NULL },
    /* ---------------------- C Y R ---------------------------
          symbol     charnum    family    class	    function
    -------------------------------------------------------- */
    /* ---
     * undefined: 20,21,28,29,33-59,61,63,64,91,92,93,96,123,124
     * ---------------------------------------------------------- */
    /* --- special characters --- */
    { "\\cyddot",	32,	CYR10,   VARIABLE,	NULL },
    /* ---See amsfndoc.dvi Figure 1 Input Conventions for AMS cyrillic--- */
    { "A",		65,	CYR10,   VARIABLE,	NULL },
    { "a",		97,	CYR10,   VARIABLE,	NULL },
    { "B",		66,	CYR10,   VARIABLE,	NULL },
    { "b",		98,	CYR10,   VARIABLE,	NULL },
    { "V",		86,	CYR10,   VARIABLE,	NULL },
    { "v",		118,	CYR10,   VARIABLE,	NULL },
    { "G",		71,	CYR10,   VARIABLE,	NULL },
    { "g",		103,	CYR10,   VARIABLE,	NULL },
    { "D",		68,	CYR10,   VARIABLE,	NULL },
    { "d",		100,	CYR10,   VARIABLE,	NULL },
    { "Dj",		6,	CYR10,   VARIABLE,	NULL },
    { "DJ",		6,	CYR10,   VARIABLE,	NULL },
    { "dj",		14,	CYR10,   VARIABLE,	NULL },
    { "E",		69,	CYR10,   VARIABLE,	NULL },
    { "e",		101,	CYR10,   VARIABLE,	NULL },
    { "\\\"E",		19,	CYR10,   VARIABLE,	NULL },
    { "\\\"e",		27,	CYR10,   VARIABLE,	NULL },
    { "\\=E",		5,	CYR10,   VARIABLE,	NULL },
    { "\\=e",		13,	CYR10,   VARIABLE,	NULL },
    { "Zh",		17,	CYR10,   VARIABLE,	NULL },
    { "ZH",		17,	CYR10,   VARIABLE,	NULL },
    { "zh",		25,	CYR10,   VARIABLE,	NULL },
    { "Z",		90,	CYR10,   VARIABLE,	NULL },
    { "z",		122,	CYR10,   VARIABLE,	NULL },
    { "I",		73,	CYR10,   VARIABLE,	NULL },
    { "i",		105,	CYR10,   VARIABLE,	NULL },
    { "\\=I",		4,	CYR10,   VARIABLE,	NULL },
    { "\\=\\i",		12,	CYR10,   VARIABLE,	NULL },
    { "J",		74,	CYR10,   VARIABLE,	NULL },
    { "j",		106,	CYR10,   VARIABLE,	NULL },
    { "\\u I",		18,	CYR10,   VARIABLE,	NULL },
    { "\\u\\i",		26,	CYR10,   VARIABLE,	NULL },
    { "K",		75,	CYR10,   VARIABLE,	NULL },
    { "k",		107,	CYR10,   VARIABLE,	NULL },
    { "L",		76,	CYR10,   VARIABLE,	NULL },
    { "l",		108,	CYR10,   VARIABLE,	NULL },
    { "Lj",		1,	CYR10,   VARIABLE,	NULL },
    { "LJ",		1,	CYR10,   VARIABLE,	NULL },
    { "lj",		9,	CYR10,   VARIABLE,	NULL },
    { "M",		77,	CYR10,   VARIABLE,	NULL },
    { "m",		109,	CYR10,   VARIABLE,	NULL },
    { "N",		78,	CYR10,   VARIABLE,	NULL },
    { "n",		110,	CYR10,   VARIABLE,	NULL },
    { "Nj",		0,	CYR10,   VARIABLE,	NULL },
    { "NJ",		0,	CYR10,   VARIABLE,	NULL },
    { "nj",		8,	CYR10,   VARIABLE,	NULL },
    { "O",		79,	CYR10,   VARIABLE,	NULL },
    { "o",		111,	CYR10,   VARIABLE,	NULL },
    { "P",		80,	CYR10,   VARIABLE,	NULL },
    { "p",		112,	CYR10,   VARIABLE,	NULL },
    { "R",		82,	CYR10,   VARIABLE,	NULL },
    { "r",		114,	CYR10,   VARIABLE,	NULL },
    { "S",		83,	CYR10,   VARIABLE,	NULL },
    { "s",		115,	CYR10,   VARIABLE,	NULL },
    { "T",		84,	CYR10,   VARIABLE,	NULL },
    { "t",		116,	CYR10,   VARIABLE,	NULL },
    { "\\\'C",		7,	CYR10,   VARIABLE,	NULL },
    { "\\\'c",		15,	CYR10,   VARIABLE,	NULL },
    { "U",		85,	CYR10,   VARIABLE,	NULL },
    { "u",		117,	CYR10,   VARIABLE,	NULL },
    { "F",		70,	CYR10,   VARIABLE,	NULL },
    { "f",		102,	CYR10,   VARIABLE,	NULL },
    { "Kh",		72,	CYR10,   VARIABLE,	NULL },
    { "KH",		72,	CYR10,   VARIABLE,	NULL },
    { "kh",		104,	CYR10,   VARIABLE,	NULL },
    { "Ts",		67,	CYR10,   VARIABLE,	NULL },
    { "TS",		67,	CYR10,   VARIABLE,	NULL },
    { "ts",		99,	CYR10,   VARIABLE,	NULL },
    { "Ch",		81,	CYR10,   VARIABLE,	NULL },
    { "CH",		81,	CYR10,   VARIABLE,	NULL },
    { "ch",		113,	CYR10,   VARIABLE,	NULL },
    { "Dzh",		2,	CYR10,   VARIABLE,	NULL },
    { "DZH",		2,	CYR10,   VARIABLE,	NULL },
    { "dzh",		10,	CYR10,   VARIABLE,	NULL },
    { "Sh",		88,	CYR10,   VARIABLE,	NULL },
    { "SH",		88,	CYR10,   VARIABLE,	NULL },
    { "sh",		120,	CYR10,   VARIABLE,	NULL },
    { "Shch",		87,	CYR10,   VARIABLE,	NULL },
    { "SHCH",		87,	CYR10,   VARIABLE,	NULL },
    { "shch",		119,	CYR10,   VARIABLE,	NULL },
    { "\\Cdprime",	95,	CYR10,   VARIABLE,	NULL },
    { "\\cdprime",	127,	CYR10,   VARIABLE,	NULL },
    { "Y",		89,	CYR10,   VARIABLE,	NULL },
    { "y",		121,	CYR10,   VARIABLE,	NULL },
    { "\\Cprime",	94,	CYR10,   VARIABLE,	NULL },
    { "\\cprime",	126,	CYR10,   VARIABLE,	NULL },
    { "\\`E",		3,	CYR10,   VARIABLE,	NULL },
    { "\\`e",		11,	CYR10,   VARIABLE,	NULL },
    { "Yu",		16,	CYR10,   VARIABLE,	NULL },
    { "YU",		16,	CYR10,   VARIABLE,	NULL },
    { "yu",		24,	CYR10,   VARIABLE,	NULL },
    { "Ya",		23,	CYR10,   VARIABLE,	NULL },
    { "YA",		23,	CYR10,   VARIABLE,	NULL },
    { "ya",		31,	CYR10,   VARIABLE,	NULL },
    { "\\Dz",		22,	CYR10,   VARIABLE,	NULL },
    { "\\dz",		30,	CYR10,   VARIABLE,	NULL },
    { "N0",		125,	CYR10,   VARIABLE,	NULL },
    { "<",		60,	CYR10,   VARIABLE,	NULL },
    { ">",		62,	CYR10,   VARIABLE,	NULL },
    /* --- trailer record --- */
    { NULL,		-999,	-999,	-999,		NULL }
 }
#endif /* INITVALS */
 ; /* --- end-of-symtable[] --- */

/* ======================= END-OF-FILE MIMETEX.H ========================= */
#endif

