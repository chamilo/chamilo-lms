/****************************************************************************
 *
 * Copyright (c) 2002, John Forkosh Associates, Inc.  All rights reserved.
 * --------------------------------------------------------------------------
 * This file is part of mimeTeX, which is free software. You may redistribute
 * and/or modify it under the terms of the GNU General Public License,
 * version 2 or later, as published by the Free Software Foundation.
 *      MimeTeX is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY, not even the implied warranty of MERCHANTABILITY.
 * See the GNU General Public License for specific details.
 *      By using mimeTeX, you warrant that you have read, understood and
 * agreed to these terms and conditions, and that you are at least 18 years
 * of age and possess the legal right and ability to enter into this
 * agreement and to use mimeTeX in accordance with it.
 *      Your mimeTeX distribution should contain a copy of the GNU General
 * Public License.  If not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * --------------------------------------------------------------------------
 *
 * Program:	gfuntype  [-g gformat]  [-u isnoname] [-m msglevel]
 *		[-n fontname]  [infile [outfile]]
 *
 * Purpose:	Parses output from  gftype -i
 *		and writes pixel bitmap data of the characters
 *		in a format suitable for a C header file, etc.
 *
 * --------------------------------------------------------------------------
 *
 * Command-line Arguments:
 *		--- args can be in any order ---
 *		infile		name of input file
 *				(defaults to stdin if no filenames given)
 *		outfile		name of output file
 *				(defaults to stdout if <2 filenames given)
 *		-g gformat	gformat=1(default) for bitmap representation,
 *				or 2,3 for 8-bit,4-bit .gf-like compression,
 *				or 0 to choose smallest format.
 *				Add 10 (gformat=10,12,13,14) to embed scan
 *				line repeat counts in format.
 *		-u isnoname	isnoname=1(default) to output symbols not
 *				defined/named in mimetex.h, or 0 to omit them
 *		-m msglevel	verbose if msglevel>=9 (vv if >=99)
 *		-n fontname	string used for fontname
 *				(defaults to noname)
 *
 * Exits:	0=success,  1=some error
 *
 * Notes:     o	To compile
 *		cc gfuntype.c mimetex.c -lm -o gfuntype
 *		needs mimetex.c and mimetex.h
 *
 * Source:	gfuntype.c
 *
 * --------------------------------------------------------------------------
 * Revision History:
 * 09/22/02	J.Forkosh	Installation.
 * 10/11/05	J.Forkosh	.gf-style format options added.
 *
 ****************************************************************************/

/* --------------------------------------------------------------------------
standard headers, program parameters, global data and macros
-------------------------------------------------------------------------- */
/* --- standard headers --- */
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
/* --- application headers --- */
/* #define SIGNEDCHAR */
#include "mimetex.h"
/* --- parameters either -D defined on cc line, or defaulted here --- */
#ifndef	MSGLEVEL
  #define MSGLEVEL 0
#endif
#ifndef	GFORMAT
  #define GFORMAT 1
#endif
#ifndef ISREPEAT
  #define ISREPEAT 1
#endif
/* --- message level (verbose test) --- */
static	int msglevel = MSGLEVEL;	/* verbose if msglevel >= 9 */
static	FILE *msgfp;			/* verbose output goes here */
/* --- output file format --- */
static	int isnoname = 1;		/* true to output unnamed symbols */
static	char *noname = "(noname)";	/* char name used if lookup fails */
static	int gformat = GFORMAT;		/* 1=bitmap, 2=.gf-like */
static	int isrepeat = ISREPEAT;	/* true to store line repeat counts*/
/* extern int imageformat; */		/* as per gformat, 1=bitmap,2=.gf */
/* --- miscellaneous other data --- */
#define	CORNER_STUB ".<--"		/* start of upper,lower-left line */
#define	BLANKCHAR_STUB "character is entirely blank" /* signals blank char */
#define	TYPECAST    "(pixbyte *)"	/* typecast for pixmap string */

/* ==========================================================================
 * Function:	main() for gfuntype.c
 * Purpose:	interprets command-line args, etc
 * --------------------------------------------------------------------------
 * Command-Line Arguments:
 *		See above
 * --------------------------------------------------------------------------
 * Returns:	0=success, 1=some error
 * --------------------------------------------------------------------------
 * Notes:     o
 * ======================================================================= */
/* --- entry point --- */
int	main ( int argc, char *argv[] )
{
/* --------------------------------------------------------------------------
Allocations and Declarations
-------------------------------------------------------------------------- */
int	argnum = 0;		/* argv[] index for command-line args */
int	inarg=0, outarg=0;	/* argv[] indexes for infile, outfile */
int	iserror = 1;		/* error signal */
int	charnum,		/* character number (nextchar->charnum) */
	nchars = 0;		/* #chars in font */
char	fontname[99] = "noname", /* font name */
	*getcharname();		/* get character name from its number */
FILE	/* *fopen(),*/ *infp=stdin, *outfp=stdout; /* init file pointers */
chardef	*getnextchar(), *nextchar, /* read and parse next char in infp */
	*fontdef[256];		/* chars stored using charnum as index */
int	cstruct_chardef();	/* emit C struct for a character map */
int	type_raster();		/* display debugging output */
char	*copyright =		/* copyright, gnu/gpl notice */
 "+-----------------------------------------------------------------------+\n"
 "|gfuntype ver 1.00, Copyright(c) 2002-2003, John Forkosh Associates, Inc|\n"
 "+-----------------------------------------------------------------------+\n"
 "| gfuntype is free software licensed to you under terms of the GNU/GPL, |\n"
 "|           and comes with absolutely no warranty whatsoever.           |\n"
 "+-----------------------------------------------------------------------+";
/* --------------------------------------------------------------------------
interpret command-line arguments
-------------------------------------------------------------------------- */
while ( argc > ++argnum )	/* check for flags and filenames */
    if ( *argv[argnum] == '-' )	/* got some '-' flag */
      {
      char flag = tolower(*(argv[argnum]+1)); /* char following '-' */
      argnum++;			/* arg following flag is usually its value */
      switch ( flag )		/* see what user wants to tell us */
	{
	/* --- no usage for clueless users yet --- */
	default:  exit(iserror); /* exit quietly for unrecognized input */
	/* --- adjustable program parameters (not checking input) --- */
	case 'g': gformat  = atoi(argv[argnum]);
		  isrepeat = (gformat>=10?1:0);
		  gformat  = gformat%10;         break;
	case 'u': isnoname = atoi(argv[argnum]); break;
	case 'm': msglevel = atoi(argv[argnum]); break;
	case 'n': strcpy(fontname,argv[argnum]); break;
	} /* --- end-of-switch() --- */
      } /* --- end-of-if(*argv[]=='-') --- */
    else			/* this arg not a -flag, so it must be... */
      if ( inarg == 0 )		/* no infile arg yet */
	inarg = argnum;		/* so use this one */
      else			/* we already have an infile arg */
	if ( outarg == 0 )	/* but no outfile arg yet */
	  outarg = argnum;	/* so use this one */
/* --- set verbose file ptr --- */
msgfp = (outarg>0? stdout : stderr); /* use stdout or stderr */
/* --- emit copyright, gnu/gpl notice --- */
fprintf(msgfp,"%s\n",copyright); /* display copyright, gnu/gpl info */
/* --- display input args if verbose output --- */
if ( msglevel >= 9 )		/* verbose output requested */
  fprintf(msgfp,"gfuntype> infile=%s outfile=%s, fontname=%s format=%d.%d\n",
  (inarg>0?argv[inarg]:"stdin"), (outarg>0?argv[outarg]:"stdout"),
  fontname, gformat,isrepeat);
/* --------------------------------------------------------------------------
initialization
-------------------------------------------------------------------------- */
/* --- initialize font[] array --- */
for ( charnum=0; charnum<256; charnum++ ) /*for each possible char in font*/
  fontdef[charnum] = (chardef *)NULL;	/* char doesn't exist yet */
/* --- open input file (if necessary) --- */
if ( inarg > 0 )		/* input from file, not from stdin */
  if ( (infp = fopen(argv[inarg],"r")) == NULL ) /*try to open input file*/
    { fprintf(msgfp,"gfuntype> can't open %s for read\n",argv[inarg]);
      goto end_of_job; }	/* report error and quit */
/* --- set format for mimetex.c functions --- */
if ( gformat<0 || gformat>3 ) gformat=1; /* sanity check */
/* if ( gformat == 1 ) imageformat = 1;	*/ /* force bitmap format */
/* else gformat = imageformat = 2; */	/* or force .gf format */
/* --------------------------------------------------------------------------
process input file
-------------------------------------------------------------------------- */
while ( (nextchar=getnextchar(infp)) != NULL ) /* get each char in file */
  {
  /* --- display character info --- */
  if ( msglevel >= 9 )			/* verbose output requested */
    fprintf(msgfp,"gfuntype> Char#%3d, loc %4d: ul=(%d,%d) ll=(%d,%d)\n",
    nextchar->charnum, nextchar->location,
    nextchar->topleftcol,nextchar->toprow,
    nextchar->botleftcol,nextchar->botrow);
  if ( msglevel >= 19 )			/* if a bit more verbose */
    type_raster(&(nextchar->image),msgfp); /*display ascii image of raster*/
  /* --- store character in font */
  charnum = nextchar->charnum;		/* get char number of char in font */
  if ( charnum>=0 && charnum<=255 )	/* check for valid range */
    fontdef[charnum] = nextchar;	/* store char in font */
  } /* --- end-of-while(charnum>0) --- */
/* --------------------------------------------------------------------------
generate output file
-------------------------------------------------------------------------- */
/* --- open output file (if necessary) --- */
if ( outarg > 0 )		/* output to a file, not to stdout */
  if ( (outfp = fopen(argv[outarg],"w")) == NULL ) /*try to open output file*/
    { fprintf(msgfp,"gfuntype> can't open %s for write\n",argv[outarg]);
      goto end_of_job; }	/* report error and quit */
/* --- header lines --- */
fprintf(outfp,"/%c --- fontdef for %s --- %c/\n", '*',fontname,'*');
fprintf(outfp,"static\tchardef %c%s[] =\n   {\n", ' ',fontname);
/* --- write characters comprising font --- */
for ( charnum=0; charnum<256; charnum++ ) /*for each possible char in font*/
 if ( fontdef[charnum] != (chardef *)NULL ) /*check if char exists in font*/
  { char *charname = getcharname(fontname,charnum);
    if ( charname!=NULL || isnoname ) {	/* char defined or want undefined */
     if ( ++nchars > 1 )		/* bump count */
      fprintf(outfp,",\n");		/* and terminate preceding chardef */
     fprintf(outfp,"      /%c --- pixel bitmap for %s char#%d %s --- %c/\n",
      '*',fontname,charnum,(charname==NULL?noname:charname),'*');
     cstruct_chardef(fontdef[charnum],outfp,6); } /*emit chardef struct*/
    else
     if(0)fprintf(outfp,"NULL");	/* no character in this position */
  } /* --- end-of-if(fontdef[]!=NULL) --- */
 else
  if(0)fprintf(outfp,"NULL");		/* no character in this position */
/* --- write trailer chardef and closing brace --- */
fprintf(outfp,",\n");			/* finish up last map from loop */
fprintf(outfp,"      /%c --- trailer  --- %c/\n",'*','*'); /* trailer... */
fprintf(outfp,"      { -99, -999,  0,0,0,0, { 0,0,0,0, %s\"\\0\" }  }\n",
     TYPECAST);
fprintf(outfp,"   } ;\n");		/* terminating }; for fontdef */
/* --------------------------------------------------------------------------
end-of-job
-------------------------------------------------------------------------- */
/* --- reset error status for okay exit --- */
iserror = 0;
/* --- close files (if they're open and not stdin/out) --- */
end_of_job:
  if (  infp!=NULL &&  infp!=stdin  ) fclose( infp);
  if ( outfp!=NULL && outfp!=stdout ) fclose(outfp);
exit ( iserror );
} /* --- end-of-function main() --- */


/* ==========================================================================
 * Function:	getnextchar ( fp )
 * Purpose:	Reads and parses the next character definition on fp,
 *		and returns a new chardef struct describing that character.
 * --------------------------------------------------------------------------
 * Arguments:	fp (I)		FILE *  to input file
 *				(containing output from  gftype -i)
 * Returns:	( chardef * )	ptr to chardef struct describing character,
 *				or NULL for eof or any error
 * --------------------------------------------------------------------------
 * Notes:     o	fp is left so the next line read from it will be
 *		the one following the final .<-- line.
 * ======================================================================= */
/* --- entry point --- */
chardef	*getnextchar ( FILE *fp )
{
/* --------------------------------------------------------------------------
Allocations and Declarations
-------------------------------------------------------------------------- */
chardef	*new_chardef(), *nextchar=(chardef *)NULL; /*ptr returned to caller*/
int	delete_chardef();		/* free allocated memory if error */
int	findnextchar(), charnum,location; /* get header line for next char */
int	rasterizechar();		/* ascii image --> raster pixmap */
int	parsestat=(-999), parsecorner(); /* get col,row from ".<--" line */
char	*readaline();			/* read next line from fp */
/* --------------------------------------------------------------------------
initialization
-------------------------------------------------------------------------- */
while ( parsestat == (-999) ) {		/* flush entirely blank characters */
  /* --- find and interpret header line for next character --- */
  charnum = findnextchar(fp,&location);	/* read and parse header line */
  if ( charnum < 0 ) goto error;	/* eof or error, no more chars */
  /* --- allocate a new chardef struct and begin populating it --- */
  if ( nextchar == (chardef *)NULL )	/* haven't allocated chardef yet */
    if ( (nextchar=new_chardef())	/* allocate a new chardef */
    ==   (chardef *)NULL ) goto error;	/* and quit if we failed */
  nextchar->charnum = charnum;		/* store charnum in struct */
  nextchar->location = location;	/* and location */
  /* --- get upper-left corner line --- */
  parsestat = parsecorner(readaline(fp), /* parse corner line */
    &(nextchar->toprow),&(nextchar->topleftcol)); /* row and col from line */
  } /* --- end-of-while(parsestat)  --- */
if ( !parsestat ) goto error;		/* quit if parsecorner() failed */
/* --------------------------------------------------------------------------
interpret character image (and parse terminating corner line)
-------------------------------------------------------------------------- */
/* --- read ascii character image and interpret as integer bitmap --- */
if ( rasterizechar(fp,&nextchar->image) != 1 ) /* parse image of char */
  goto error;				/* and quit if failed */
/* --- get lower-left corner line --- */
if ( !parsecorner(readaline(NULL),	/* reread and parse corner line */
&(nextchar->botrow),&(nextchar->botleftcol)) ) /* row and col from line */
  goto error;				/* and quit if failed */
/* --------------------------------------------------------------------------
done
-------------------------------------------------------------------------- */
goto end_of_job;			/* skip error return if successful */
error:
  if ( nextchar != (chardef *)NULL )	/* have an allocated chardef */
    delete_chardef(nextchar);		/* so deallocate it */
  nextchar = (chardef *)NULL;		/* and reset ptr to null for error */
end_of_job:
  return ( nextchar );			/* back with chardef or null */
} /* --- end-of-function getnextchar() --- */


/* ==========================================================================
 * Function:	getcharname ( fontname, charnum )
 * Purpose:	Looks up charnum for the family specified by fontname
 *		and returns the corresponding charname.
 * --------------------------------------------------------------------------
 * Arguments:	fontname (I)	char * containing fontname for font family
 *				(from -n switch on command line)
 *		charnum (I)	int containing the character number
 *				whose corresponding name is wanted.
 * Returns:	( char * )	ptr to character name
 *				or NULL if charnum not found in table
 * --------------------------------------------------------------------------
 * Notes:     o
 * ======================================================================= */
/* --- entry point --- */
char	*getcharname ( char *fontname, int charnum )
{
/* --------------------------------------------------------------------------
Allocations and Declarations
-------------------------------------------------------------------------- */
/* --- recognized font family names and our corresponding numbers --- */
static	char *fnames[] =	/*font name from -n switch on command line*/
	{ "cmr","cmmib","cmmi","cmsy","cmex","bbold","rsfs","stmary", NULL };
static	int    fnums[] =	/* corresponding mimetex fontfamily number*/
	{ CMR10,CMMIB10,CMMI10,CMSY10,CMEX10,BBOLD10,RSFS10,STMARY10,  -1 };
static	int    offsets[] =	/* symtable[ichar].charnum = charnum-offset*/
	{     0,      0,     0,     0,     0,      0,    65,       0,  -1 };
/* --- other local declarations --- */
char	*charname = NULL;	/* character name returned to caller */
char	flower[99] = "noname";	/* lowercase caller's fontname */
int	ifamily = 0,		/* fnames[] (and fnums[],offsets[]) index */
	offset = 0,		/* offsets[ifamily] */
	ichar = 0;		/* loop index */
/* --------------------------------------------------------------------------
lowercase caller's fontname and look it up in fnames[]
-------------------------------------------------------------------------- */
/* --- lowercase caller's fontname --- */
for ( ichar=0; *fontname!='\000'; ichar++,fontname++ )/*lowercase each char*/
  flower[ichar] = (isalpha(*fontname)? tolower(*fontname) : *fontname);
flower[ichar] = '\000';		/* null-terminate lowercase fontname */
if ( strlen(flower) < 2 ) goto end_of_job; /* no lookup match possible */
/* --- look up lowercase fontname in our fnames[] table --- */
for ( ifamily=0; ;ifamily++ )	/* check fnames[] for flower */
  if ( fnames[ifamily] == NULL ) goto end_of_job; /* quit at end-of-table */
  else if ( strstr(flower,fnames[ifamily]) != NULL ) break; /* found it */
offset = offsets[ifamily];	/* symtable[ichar].charnum = charnum-offset*/
ifamily = fnums[ifamily];	/* xlate index to font family number */
/* --------------------------------------------------------------------------
now look up name for caller's charnum in ifamily, and return it to caller
-------------------------------------------------------------------------- */
/* --- search symtable[] for charnum in ifamily --- */
for ( ichar=0; ;ichar++ )	/*search symtable[] for charnum in ifamily*/
  if ( symtable[ichar].symbol == NULL ) goto end_of_job; /* end-of-table */
  else
    if ( symtable[ichar].family == ifamily /* found desired family */
    &&   symtable[ichar].handler == NULL ) /* and char isn't a "dummy" */
      if ( symtable[ichar].charnum == charnum-offset ) break; /*got charnum*/
/* --- return corresponding charname to caller --- */
charname = symtable[ichar].symbol; /* pointer to symbol name in table */
end_of_job:
  if ( charname==NULL && isnoname ) /* want unnamed/undefined chars */
    charname = noname;		/* so replace null return with noname */
  return ( charname );
} /* --- end-of-function getcharname() --- */


/* ==========================================================================
 * Function:	findnextchar ( fp, location )
 * Purpose:	Finds next "beginning of char" line in fp
 *		and returns the character number,
 *		and (optionally) location if arg provided.
 * --------------------------------------------------------------------------
 * Arguments:	fp (I)		FILE *  to input file
 *				(containing output from  gftype -i)
 *		location (O)	int *  returning "location" of character
 *				(or pass NULL and it won't be returned)
 * Returns:	( int )		character number,
 *				or -1 for eof or any error
 * --------------------------------------------------------------------------
 * Notes:     o	fp is left so the next line read from it will be
 *		the one following the "beginning of char" line
 * ======================================================================= */
/* --- entry point --- */
int	findnextchar ( FILE *fp, int *location )
{
/* --------------------------------------------------------------------------
Allocations and Declarations
-------------------------------------------------------------------------- */
static	char keyword[99]="beginning of char "; /*signals start of next char*/
char	*readaline(), *line;	/* read next line from fp */
char	*strstr(), *strchr(), *delim; /* search line for substring, char */
char	token[99];		/* token extracted from line */
int	charnum = (-1);		/* character number returned to caller */
/* --------------------------------------------------------------------------
keep reading lines until eof or keyword found
-------------------------------------------------------------------------- */
while ( (line=readaline(fp)) != NULL ) /* read lines until eof */
  {
  if ( msglevel >= 999 )	/* very, very verbose output requested */
    fprintf(msgfp,"nextchar> line = %s\n",line);
  if ( (delim=strstr(line,keyword)) != NULL ) /* found keyword on line */
    {
    /* --- get character number from line --- */
    strcpy(token,delim+strlen(keyword)); /* char num follows keyword */
    charnum = atoi(token);	/* interpret token as integer charnum */
    /* --- get location at beginning of line --- */
    if ( location != (int *)NULL )  /* caller wants location returned */
      if ( (delim=strchr(line,':')) != NULL ) /* location precedes colon */
	{ *delim = '\000';	/* terminate line after location */
	  *location = atoi(line); } /* interpret location as integer */
    break;			/* back to caller with charnum */
    } /* --- end-of-if(delim!=NULL) --- */
  } /* --- end-of-while(line!=NULL) --- */
return ( charnum );		/* back to caller with char number or -1 */
} /* --- end-of-function findnextchar() --- */


/* ==========================================================================
 * Function:	rasterizechar ( fp, rp )
 * Purpose:	Reads and parses subsequent lines from fp
 *		(until a terminating ".<--" line),
 *		representing the ascii image of the character in fp,
 *		and returns the results in raster struct rp
 * --------------------------------------------------------------------------
 * Arguments:	fp (I)		FILE *  to input file
 *				(containing output from  gftype -i)
 *				positioned immediately after top .<-- line,
 *				ready to read first line of ascii image
 *		rp (O)		raster *  returning the rasterized
 *				character represented on fp as an ascii image
 * Returns:	( int )		1=okay, or 0=eof or any error
 * --------------------------------------------------------------------------
 * Notes:     o	fp is left so the last line (already) read from it
 *		contains the terminating .<-- corner information
 *		(readaline(NULL) will reread this last line)
 *	      o	char images on fp can be no wider than 31 pixels
 * ======================================================================= */
/* --- entry point --- */
int	rasterizechar ( FILE *fp, raster *image )
{
/* --------------------------------------------------------------------------
Allocations and Declarations
-------------------------------------------------------------------------- */
char	*readaline(), *line;	/* read next scan line for char from fp */
unsigned char bitvec[1024][128]; /* scan lines parsed up to 1024x1024 bits */
int	bitcmp();		/* compare bit strings */
int	height = 0,		/* #scan lines in fp comprising char */
	width = 0;		/* #chars on longest scan line */
int	iscan,			/* bitvec[] index */
	ibit;			/* bit along scan (i.e., 0...width-1) */
int	isokay = 0;		/* returned status, init for failure */
/* --- bitmap and .gf-formatted image info (we'll choose smallest) --- */
int	iformat = gformat;	/*0=best, 1=bitmap, 2=8-bit.gf, 3=4-bit.gf*/
unsigned char gfpixcount[2][65536]; /* .gf black/white flips (max=64K) */
int	npixcounts[2] = {9999999,9999999}; /* #counts for 8-bit,4-bit .gf */
int	nbytes1=9999999,nbytes2=9999999,nbytes3=9999999;/*#bytes for format*/
/* --------------------------------------------------------------------------
read lines till ".<--" terminator, and construct one vector[] int per line
-------------------------------------------------------------------------- */
memset(bitvec,0,128*1024);	/* zero-fill bitvec[] */
while ( (line=readaline(fp)) != NULL ) /* read lines until eof */
  {
  /* --- allocations and declarations --- */
  int	icol, ncols=strlen(line); /* line[] column index, #cols in line[] */
  /* --- check for end-of-char (when we encounter corner line) --- */
  if ( memcmp(line,CORNER_STUB,strlen(CORNER_STUB)) == 0 ) /* corner line */
    break;			/* so done with loop */
  /* --- parse line (encode asterisks comprising character image) --- */
  memset(bitvec[height],0,128);	/* first zero out all bits */
  for ( icol=0; icol<ncols; icol++ ) /* now check line[] for asterisks */
    if ( line[icol] == '*' )	/* we want to set this bit */
      {	setlongbit(bitvec[height],icol); /* set bit */
	if ( icol >= width ) width=icol+1; } /* and check for new width */
  height++;			/* bump character height */
  } /* --- end-of-while(line!=NULL) --- */
if ( height<1 || width<1 )	/* some problem parsing character */
  goto end_of_job;		/* so quit */
/* --------------------------------------------------------------------------
init image values
-------------------------------------------------------------------------- */
if ( image->pixmap != NULL )	/* hmm, somebody already allocated memory */
  free((void *)image->pixmap);	/* so just free it */
image->width = width;		/* set image width within raster struct */
image->height = height;		/* and height */
image->format = gformat;	/* set format (will be reset below) */
image->pixsz = 1;		/* #bits per pixel (or #counts in .gf fmt) */
if ( gformat==0 || gformat==1 )	/* bitmap representation allowed */
  { nbytes1 = pixmapsz(image);	/* #bytes needed for bitmap */
    iformat = 1; }		/* default to bitmap format */
/* --------------------------------------------------------------------------
perform .gf-like compression on image in bitvec
-------------------------------------------------------------------------- */
if ( gformat == 0		/* choose optimal/smallest respresentation */
||   gformat==2 || gformat==3 )	/* .gf-like compressed representation */
 {
 /* --- try both 8-bits/count and 4-bits/count for best compression --- */
 int	maxbitcount[2] = {254,14}; /* don't count too much in one byte */
 int	repeatcmds[2]  = {255,15}; /* opcode for repeat/duplicate count */
 int	minbytes = 0;		/* #bytes needed for smallest format */
 for ( iformat=2; iformat<=3; iformat++ ) { /* 2=8-bit packing, 3=4-bit */
  int	gfbitcount = 0,		/* count of consecutive gfbitval's */
	gfbitval = 0,		/* begin with count of leading 0's */
	pixcount = 0;		/* #packed bytes (#black/white flips) */
  unsigned char *gfcount = gfpixcount[iformat-2]; /*counts for this format*/
  if ( gformat!=0 && gformat!=iformat ) /* this format not allowed */
    continue;			/* so just skip it */
  for ( iscan=0; iscan<height; iscan++ ) /* for each integer in bitvec[] */
   {
   int	bitval = 0;		/* current actual pixel value */
   int	nrepeats=0, nextreps=0;	/* #duplicate lines below current,next line*/
   /* --- check for repeated/duplicate scan lines --- */
   if ( isrepeat		/* we're storing scan line repeat counts */
   &&   iscan < height-1 ) {	/* current scan line isn't the last line */
    /* --- count repeats --- */
    int jscan = iscan;		/* compare current scan with lines below it*/
    while ( ++jscan < height ) { /* until last scan line */
     if (nrepeats == jscan-iscan-1) /*no intervening non-identical lines*/
      if ( bitcmp(bitvec[iscan],bitvec[jscan],width) == 0 ) /* identical */
       nrepeats++;		/* so bump repeat count */
     if ( jscan > iscan+1 )	/* we're below next line */
      if (nextreps == jscan-iscan-2) /*no intervening non-identical lines*/
       if ( bitcmp(bitvec[iscan+1],bitvec[jscan],width) == 0 )/*identical*/
	nextreps++; }		/* so bump next lline repeat count */
    /* --- set repeat command and count --- */
    if ( nrepeats > 0 ) {	/* found repeated lines below current */
     int maxrepeats = maxbitcount[iformat-2]; /*max count/repeats per byte*/
     if ( nrepeats > maxrepeats ) nrepeats=maxrepeats; /* don't exceed max */
     {setbyfmt(iformat,gfcount,pixcount,repeatcmds[iformat-2]);} /*set cmd*/
     {setbyfmt(iformat,gfcount,pixcount+1,nrepeats);} /* set #repeats */
     pixcount += 2; }		/* don't bump pixcount within macros */
    } /* --- end-of-if(isrepeat) --- */
   /* --- set bit counts for current scan line --- */
   for ( ibit=0; ibit<width; ibit++ )	/* for all bits in this scanline */
    {
    bitval = getlongbit(bitvec[iscan],ibit); /* check actual pixel value */
    if ( bitval != gfbitval ) {	/* black-to-white edge (or vice versa) */
      {setbyfmt(iformat,gfcount,pixcount,gfbitcount);} /*set byte or nibble*/
      pixcount++;		/* don't bump pixcount within macro */
      gfbitcount = 0;		/* reset consecutive bit count */
      gfbitval = 1-gfbitval; }	/* flip bit to be counted */
    else			/* check count if continuing with same val */
     if ( gfbitcount >= maxbitcount[iformat-2] ) { /* max count per byte */
      {setbyfmt(iformat,gfcount,pixcount,gfbitcount);} /*set byte or nibble*/
      clearbyfmt(iformat,gfcount,pixcount+1); /*followed by dummy 0 count*/
      pixcount += 2;		/* don't bump pixcount within macros */
      gfbitcount = 0; }		/* reset consecutive bit count */
    if ( bitval == gfbitval )	/* same bit val as preceding, or first new */
      gfbitcount++;		/* so just count another pixel */
    } /* --- end-of-for(ibit) --- */
   /* --- adjust for repeated scan lines --- */
   iscan += nrepeats;		/* skip repeated/duplicate scan lines */
   if ( nrepeats>0 || nextreps>0 ) /* emit count to align on full scan */
    if ( iscan < height-1 )	/* have another scan line below this one */
     if ( gfbitcount > 0 ) {	/* should always have some final count */
      {setbyfmt(iformat,gfcount,pixcount,gfbitcount);} /*set byte or nibble*/
      pixcount++;		/* don't bump pixcount within macro */
      gfbitcount = 0;		/* reset consecutive bit count */
      if ( bitval == getlongbit(bitvec[iscan+1],0) ) { /* same bit value */
       clearbyfmt(iformat,gfcount,pixcount); /*so we need a dummy 0 count*/
       pixcount++; }		/* don't bump pixcount within macros */
      else			/* bitval flips at start of next line */
       gfbitval = 1-gfbitval;	/* so flip bit to be counted */
      } /* --- end-of-if(nrepeats...gfbitcount>0) --- */
   } /* --- end-of-for(iscan) --- */
   /* --- store final count --- */
   if ( gfbitcount > 0 ) {	/* have a final count */
     {setbyfmt(iformat,gfcount,pixcount,gfbitcount);} /*set byte or nibble*/
     pixcount++; }		/* don't bump pixcount within macro */
   else				/* ended exactly after maxbitcount? */
    if ( getbyfmt(iformat,gfcount,pixcount-1) == 0 )/*have dummy 0 trailer?*/
     pixcount--;		/* remove unneeded dummy trailer */
   /* --- save count to choose smallest --- */
   npixcounts[iformat-2] = pixcount; /* save count */
   } /* --- end-of-for(iformat) --- */
 /* --- check for optimal/smallest format --- */
 nbytes2=npixcounts[0];  nbytes3=(1+npixcounts[1])/2; /* #bytes for count */
 iformat = (nbytes2<nbytes3? 2:3); /* choose smallest format */
 minbytes = (iformat==2?nbytes2:nbytes3); /* #bytes for smallest format */
 if ( gformat == 0 )		/* bitmap representation also permitted */
  if ( nbytes1 <= minbytes )	/* and it's the optimal/smallest format */
   iformat = 1;			/* so flip format */
 /* --- move results to returned image --- */
 if ( iformat != 1 ) {		/* using a .gf format */
  if ( (image->pixmap = (unsigned char *)malloc(minbytes)) /* alloc pixmap */
  == NULL ) goto end_of_job;	/* quit if failed to allocate pixmap */
  memcpy(image->pixmap,gfpixcount[iformat-2],minbytes); /*copy local counts*/
  image->format = iformat;	/* signal byte counts or nibble counts */
  image->pixsz = npixcounts[iformat-2]; /*#counts in pixmap for gformat=2,3*/
  } /* --- end-of-if(iformat!=1) --- */
 } /* --- end-of-if(gformat==2) --- */
/* --------------------------------------------------------------------------
copy each integer in bitvec[] to raster pixmap, bit by bit
-------------------------------------------------------------------------- */
if ( iformat == 1 )		/* bit-by-bit representation of image */
 {
 int	ipixel = 0;		/* pixmap index */
 /* --- first allocate image raster pixmap for character --- */
 if ( (image->pixmap = (unsigned char *)malloc(pixmapsz(image)))
 == NULL ) goto end_of_job;	/* quit if failed to allocate pixmap */
 image->format = iformat;	/* reset format */
 /* --- now store bit image in allocated raster --- */
 for ( iscan=0; iscan<height; iscan++ )	/* for each integer in bitvec[] */
  for ( ibit=0; ibit<width; ibit++ )	/* for all bits in this scanline */
    {
    if ( getlongbit(bitvec[iscan],ibit) != 0 ) /* check current scan pixel */
      { setlongbit(image->pixmap,ipixel); }
    else				/*turn off corresponding raster bit*/
      { unsetlongbit(image->pixmap,ipixel); }
    ipixel++;				/* bump image raster pixel */
    } /* --- end-of-for(iscan,ibit) --- */
 } /* --- end-of-if(gformat==1) --- */
/* --------------------------------------------------------------------------
done
-------------------------------------------------------------------------- */
isokay = 1;				/* reset flag for success */
 end_of_job:
  return ( isokay );			/* back with 1=success, 0=failure */
} /* --- end-of-function rasterizechar() --- */


/* ==========================================================================
 * Function:	parsecorner ( line, row, col )
 * Purpose:	Parses a "pixel corner" line (upper left or lower left)
 *		and returns the (col,row) information on it as integers.
 * --------------------------------------------------------------------------
 * Arguments:	line (I)	char *  to input line containing
 *				".<--This pixel's..." to be parsed
 *		row (O)		int *  returning the (,row)
 *		col (O)		int *  returning the (col,)
 * Returns:	( int )		1 if successful, or 0 for any error
 * --------------------------------------------------------------------------
 * Notes:     o
 * ======================================================================= */
/* --- entry point --- */
int	parsecorner ( char *line, int *row, int *col )
{
/* --------------------------------------------------------------------------
Allocations and Declarations
-------------------------------------------------------------------------- */
int	isokay = 0;		/* success/fail flag, init for failure */
char	field[99], *delim;	/*(col,row) field and ptr to various delims*/
/* --------------------------------------------------------------------------
extract (col,row) field from line, and interpret col and row as integers
-------------------------------------------------------------------------- */
/* --- first, check beginning of line --- */
if ( line == (char *)NULL ) goto end_of_job; /* no line supplied by caller */
/* --- check for blank line --- */
if ( strstr(line,BLANKCHAR_STUB) != NULL ) /* got entirely blank character */
  return ( -999 );			/* so return special -999 signal */
/* --- check for corner --- */
if ( memcmp(line,CORNER_STUB,strlen(CORNER_STUB)) != 0 ) /*not valid corner*/
  goto end_of_job;			/* so quit */
/* --- extract  col,row  field from line --- */
if ( (delim=strchr(line,'(')) == NULL ) goto end_of_job; /*find open paren*/
strncpy(field,delim+1,10);		/* extract next 10 chars */
field[10] = '\000';			/* and null-terminate field */
if ( (delim=strchr(field,')')) == NULL ) goto end_of_job; /*find close paren*/
*delim = '\000';			/* terminate field at close paren */
/* --- interpret col,row as integers --- */
if ( (delim=strchr(field,',')) == NULL ) goto end_of_job; /* find comma */
*delim = '\000';			/* break field into col and row */
if ( col != (int *)NULL )		/* caller gave us ptr for col */
  *col = atoi(field);			/* so return it to him */
if ( row != (int *)NULL )		/* caller gave us ptr for row */
  *row = atoi(delim+1);			/* so return it to him */
/* --------------------------------------------------------------------------
done
-------------------------------------------------------------------------- */
isokay = 1;				/* reset flag for success */
 end_of_job:
  return ( isokay );			/* back with success/fail flag */
} /* --- end-of-function parsecorner() --- */


/* ==========================================================================
 * Function:	readaline ( fp )
 * Purpose:	Reads a line from fp, strips terminating newline,
 *		and returns ptr to internal buffer
 * --------------------------------------------------------------------------
 * Arguments:	fp (I)		FILE *  to input file to be read.
 *				If null, returns line previously read.
 * Returns:	( char * )	internal buffer containing line read,
 *				or NULL for eof or error.
 * --------------------------------------------------------------------------
 * Notes:     o	fp is left on the line following the returned line
 * ======================================================================= */
/* --- entry point --- */
char	*readaline ( FILE *fp )
{
/* --------------------------------------------------------------------------
Allocations and Declarations
-------------------------------------------------------------------------- */
static	char buffer[2048];	/* static buffer returned to caller */
char	*fgets(), *bufptr=buffer; /* read line from fp */
char	*strchr(), *delim;	/* remove terminating newline */
/* --------------------------------------------------------------------------
Read line and strip trailing newline
-------------------------------------------------------------------------- */
if ( fp != NULL )			/*if null, return previous line read*/
  if ( (bufptr=fgets(buffer,2047,fp))	/* read next line from fp */
  != NULL )				/* and check that we succeeded */
    {
    if ( (delim=strchr(bufptr,'\n'))	/* look for terminating newline */
    != NULL )				/* and check that we found it */
      *delim = '\000';			/* truncate line at newline */
    } /* --- end-of-if(fgets()!=NULL) --- */
return ( bufptr );			/*back to caller with buffer or null*/
} /* --- end-of-function readaline() --- */


/* ==========================================================================
 * Function:	bitcmp ( bs1, bs2, n )
 * Purpose:	compares the first n bits of two strings
 * --------------------------------------------------------------------------
 * Arguments:	bs1 (I)		unsigned char * to first bit string
 *		bs2 (I)		unsigned char * to second bit string
 *		n (I)		int containing #bits to compare
 * Returns:	( int )		0 if first n bits are identical
 *				-1 if first unmatching bit of bs1 is 0
 *				+1 if first unmatching bit of bs2 id 0
 * --------------------------------------------------------------------------
 * Notes:     o
 * ======================================================================= */
/* --- entry point --- */
int	bitcmp ( unsigned char *bs1, unsigned char *bs2, int n )
{
/* --------------------------------------------------------------------------
Allocations and Declarations
-------------------------------------------------------------------------- */
int	icmp = 0;		/* returned to caller */
int	nbytes = n/8,		/* #full bytes we can compare with memcmp()*/
	nbits  = n%8,  ibit=0;	/* #trailing bits in last byte, index */
/* --------------------------------------------------------------------------
compare leading bytes, then trailing bits
-------------------------------------------------------------------------- */
if ( nbytes > 0 ) icmp = memcmp(bs1,bs2,nbytes); /* compare leading bytes */
if ( icmp == 0 )		/* leading bytes identical */
 if ( nbits > 0 )		/* and we have trailing bits */
  for ( ibit=0; ibit<nbits; ibit++ ) /* check each bit */
   { icmp = (int)get1bit(bs1[nbytes],ibit) - (int)get1bit(bs2[nbytes],ibit);
     if ( icmp != 0 ) break; }	/* done at first unmatched bit */
return ( icmp );		/* back to caller with -1,0,+1 */
} /* --- end-of-function bitcmp() --- */
/* --- end-of-file gfuntype.c --- */

