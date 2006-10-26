/* $Id: gifsave.c,v 1.1 2006/04/05 07:22:17 pcool Exp $ */
/**************************************************************************
 *
 *  FILE            gifsave.c
 *
 *  DESCRIPTION     Routines to create a GIF-file. See README for
 *                  a description.
 *
 *                  The functions were originally written using Borland's
 *                  C-compiler on an IBM PC -compatible computer, but they
 *                  are compiled and tested on Linux and SunOS as well.
 *
 *  WRITTEN BY      Sverre H. Huseby <sverrehu@online.no>
 *
 **************************************************************************/

#include <stdlib.h>
#include <stdio.h>
/* #include <unistd.h> */	/* (added by j.forkosh) to get STDOUT_FILENO*/
#include <string.h>		/* " */
/* --- windows-specific header info --- */
#ifndef WINDOWS			/* -DWINDOWS not supplied by user */
  #if defined(_WINDOWS) || defined(_WIN32) || defined(WIN32) \
  ||  defined(DJGPP)		/* try to recognize windows compilers */ \
  ||  defined(_USRDLL)		/* must be WINDOWS if compiling for DLL */
    #define WINDOWS		/* signal windows */
  #endif
#endif
#ifdef WINDOWS			/* " if filename=NULL passed to GIF_Create()*/
  #include <fcntl.h>		/* " OutFile=stdout used.  But Windows opens*/
  #include <io.h>		/* " stdout in char mode, and precedes every*/
				/* " 0x0A with spurious 0x0D. */
  #if defined(_O_BINARY) && !defined(O_BINARY)  /* only have _O_BINARY */
    #define O_BINARY _O_BINARY	/* make O_BINARY available, etc... */
    #define setmode  _setmode
    #define fileno   _fileno
  #endif
  #if defined(_O_BINARY) || defined(O_BINARY)  /* setmode() now available */
    #define HAVE_SETMODE	/* so we'll use setmode() */
  #endif
#endif

/* #include "gifsave.h" */	/* (j.forkosh) explcitly include header */
enum GIF_Code {
    GIF_OK = 0,
    GIF_ERRCREATE,
    GIF_ERRWRITE,
    GIF_OUTMEM
};

int  GIF_Create(const char *filename, int width, int height,
		int numcolors, int colorres);
void GIF_SetColor(int colornum, int red, int green, int blue);
void GIF_SetTransparent(int colornum);	/* (added by j.forkosh) */
int  GIF_CompressImage(int left, int top, int width, int height,
		       int (*getpixel)(int x, int y));
int  GIF_Close(void);
/* --- end-of-header gifsave.h --- */


/**************************************************************************
 *                                                                        *
 *                       P R I V A T E    D A T A                         *
 *                                                                        *
 **************************************************************************/

typedef unsigned Word;          /* at least two bytes (16 bits) */
typedef unsigned char Byte;     /* exactly one byte (8 bits) */

/* used by IO-routines */
static FILE *OutFile = NULL;    /* file to write to */
static Byte *OutBuffer = NULL;	/* (added by j.forkosh) */
static int isCloseOutFile = 0;	/* " */
int gifSize = 0;		/* " #bytes comprising gif */
int maxgifSize = 64000;		/* " max #bytes written to OutBuffer */

/* used when writing to a file bitwise */
static Byte Buffer[256];        /* there must be one more than `needed' */
static int  Index,              /* current byte in buffer */
            BitsLeft;           /* bits left to fill in current byte. These
                                 * are right-justified */

/* used by routines maintaining an LZW string table */
#define RES_CODES 2

#define HASH_FREE 0xFFFF
#define NEXT_FIRST 0xFFFF

#define MAXBITS 12
#define MAXSTR (1 << MAXBITS)

#define HASHSIZE 9973
#define HASHSTEP 2039

#define HASH(index, lastbyte) (((lastbyte << 8) ^ index) % HASHSIZE)

static Byte *StrChr = NULL;
static Word *StrNxt = NULL,
            *StrHsh = NULL,
            NumStrings;

/* used in the main routines */
typedef struct {
    Word LocalScreenWidth,
         LocalScreenHeight;
    Byte GlobalColorTableSize : 3,
         SortFlag             : 1,
         ColorResolution      : 3,
         GlobalColorTableFlag : 1;
    Byte BackgroundColorIndex;
    Byte PixelAspectRatio;
} ScreenDescriptor;

typedef struct {
    Byte Separator;
    Word LeftPosition,
         TopPosition;
    Word Width,
         Height;
    Byte LocalColorTableSize : 3,
         Reserved            : 2,
         SortFlag            : 1,
         InterlaceFlag       : 1,
         LocalColorTableFlag : 1;
} ImageDescriptor;

static int  BitsPrPrimColor,    /* bits pr primary color */
            NumColors;          /* number of colors in color table */
static int  TransparentColorIndex=(-1); /* (added by j.forkosh) */
static Byte *ColorTable = NULL;
static Word ScreenHeight,
            ScreenWidth,
            ImageHeight,
            ImageWidth,
            ImageLeft,
            ImageTop,
            RelPixX, RelPixY;   /* used by InputByte() -function */
static int  (*GetPixel)(int x, int y);



/**************************************************************************
 *                                                                        *
 *                   P R I V A T E    F U N C T I O N S                   *
 *                                                                        *
 **************************************************************************/

/*========================================================================*
 =                         Routines to do file IO                         =
 *========================================================================*/

/*-------------------------------------------------------------------------
 *
 *  NAME          Create
 *
 *  DESCRIPTION   Creates a new file, and enables referencing using the
 *                global variable OutFile. This variable is only used
 *                by these IO-functions, making it relatively simple to
 *                rewrite file IO.
 *
 *  INPUT         filename
 *                        name of file to create,
 *                        or NULL for stdout,
 *                        or if *filename='\000' then it's the address of
 *                           a memory buffer to which gif will be written
 *
 *  RETURNS       GIF_OK       - OK
 *                GIF_ERRWRITE - Error opening the file
 */
static int
Create(const char *filename)
{
    OutBuffer = NULL;				/* (added by j.forkosh) */
    isCloseOutFile = 0;				/* " */
    gifSize = 0;				/* " */
    if ( filename == NULL )			/* " */
      {	OutFile = stdout;			/* " */
	/*OutFile = fdopen(STDOUT_FILENO,"wb");*/ /* " doesn't work, */
	#ifdef WINDOWS				/* "   so instead... */
	  #ifdef HAVE_SETMODE			/* "   try to use setmode()*/
	    if ( setmode ( fileno (stdout), O_BINARY) /* to  set stdout */
	    == -1 ) ; /* handle error */	/* " to binary mode */
	  #else					/* " setmode not available */
	    #if 1				/* " */
	      freopen ("CON", "wb", stdout);	/* " freopen stdout binary */
	    #else				/* " */
	      stdout = fdopen (STDOUT_FILENO, "wb"); /*fdopen stdout binary*/
	    #endif				/* " */
	  #endif				/* " */
	#endif					/* " */
      }						/* " */
    else					/* " */
      if ( *filename != '\000' )		/* " */
	{ if ((OutFile = fopen(filename, "wb")) == NULL)
	    return GIF_ERRCREATE;
	  isCloseOutFile = 1; }			/* (added by j.forkosh) */
      else					/* " */
	OutBuffer = (Byte *)filename;		/* " */
    return GIF_OK;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          Write
 *
 *  DESCRIPTION   Output bytes to the current OutFile.
 *
 *  INPUT         buf     pointer to buffer to write
 *                len     number of bytes to write
 *
 *  RETURNS       GIF_OK       - OK
 *                GIF_ERRWRITE - Error writing to the file
 */
static int
Write(const void *buf, unsigned len)
{
    if ( OutBuffer == NULL )			/* (added by j.forkosh) */
      {	if (fwrite(buf, sizeof(Byte), len, OutFile) < len)
	  return GIF_ERRWRITE; }
    else					/* (added by j.forkosh) */
      {	if ( gifSize+len <= maxgifSize )	/* " */
	  memcpy(OutBuffer+gifSize,buf,len); }	/* " */
    gifSize += len;				/* " */
    return GIF_OK;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          WriteByte
 *
 *  DESCRIPTION   Output one byte to the current OutFile.
 *
 *  INPUT         b       byte to write
 *
 *  RETURNS       GIF_OK       - OK
 *                GIF_ERRWRITE - Error writing to the file
 */
static int
WriteByte(Byte b)
{
    if ( OutBuffer == NULL )			/* (added by j.forkosh) */
      {	if (putc(b, OutFile) == EOF)
	  return GIF_ERRWRITE; }
    else					/* (added by j.forkosh) */
      {	if ( gifSize < maxgifSize )		/* " */
	  OutBuffer[gifSize] = b; }		/* " */
    gifSize++;					/* " */
    return GIF_OK;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          WriteWord
 *
 *  DESCRIPTION   Output one word (2 bytes with byte-swapping, like on
 *                the IBM PC) to the current OutFile.
 *
 *  INPUT         w       word to write
 *
 *  RETURNS       GIF_OK       - OK
 *                GIF_ERRWRITE - Error writing to the file
 */
static int
WriteWord(Word w)
{
    if ( OutBuffer == NULL )			/* (added by j.forkosh) */
      {	if (putc(w & 0xFF, OutFile) == EOF)
	  return GIF_ERRWRITE;
	if (putc((w >> 8), OutFile) == EOF)
	  return GIF_ERRWRITE; }
    else					/* (added by j.forkosh) */
      if ( gifSize+1 < maxgifSize )		/* " */
	{ OutBuffer[gifSize] = (Byte)(w & 0xFF);  /* " */
	  OutBuffer[gifSize+1] = (Byte)(w >> 8); }  /* " */
    gifSize += 2;				/* " */
    return GIF_OK;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          Close
 *
 *  DESCRIPTION   Close current OutFile.
 */
static void
Close(void)
{
    if ( isCloseOutFile )			/* (added by j.forkosh) */
      fclose(OutFile);
    OutBuffer = NULL;				/* (added by j.forkosh) */
    isCloseOutFile = 0;				/* " */
}





/*========================================================================*
 =                                                                        =
 =                      Routines to write a bit-file                      =
 =                                                                        =
 *========================================================================*/

/*-------------------------------------------------------------------------
 *
 *  NAME          InitBitFile
 *
 *  DESCRIPTION   Initiate for using a bitfile. All output is sent to
 *                  the current OutFile using the I/O-routines above.
 */
static void
InitBitFile(void)
{
    Buffer[Index = 0] = 0;
    BitsLeft = 8;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          ResetOutBitFile
 *
 *  DESCRIPTION   Tidy up after using a bitfile
 *
 *  RETURNS       0 - OK, -1 - error
 */
static int
ResetOutBitFile(void)
{
    Byte numbytes;

    /* how much is in the buffer? */
    numbytes = Index + (BitsLeft == 8 ? 0 : 1);

    /* write whatever is in the buffer to the file */
    if (numbytes) {
        if (WriteByte(numbytes) != GIF_OK)
            return -1;

        if (Write(Buffer, numbytes) != GIF_OK)
            return -1;

        Buffer[Index = 0] = 0;
        BitsLeft = 8;
    }
    return 0;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          WriteBits
 *
 *  DESCRIPTION   Put the given number of bits to the outfile.
 *
 *  INPUT         bits    bits to write from (right justified)
 *                numbits number of bits to write
 *
 *  RETURNS       bits written, or -1 on error.
 *
 */
static int
WriteBits(int bits, int numbits)
{
    int  bitswritten = 0;
    Byte numbytes = 255;

    do {
        /* if the buffer is full, write it */
        if ((Index == 254 && !BitsLeft) || Index > 254) {
            if (WriteByte(numbytes) != GIF_OK)
                return -1;

            if (Write(Buffer, numbytes) != GIF_OK)
                return -1;

            Buffer[Index = 0] = 0;
            BitsLeft = 8;
        }

        /* now take care of the two specialcases */
        if (numbits <= BitsLeft) {
            Buffer[Index] |= (bits & ((1 << numbits) - 1)) << (8 - BitsLeft);
            bitswritten += numbits;
            BitsLeft -= numbits;
            numbits = 0;
        } else {
            Buffer[Index] |= (bits & ((1 << BitsLeft) - 1)) << (8 - BitsLeft);
            bitswritten += BitsLeft;
            bits >>= BitsLeft;
            numbits -= BitsLeft;

            Buffer[++Index] = 0;
            BitsLeft = 8;
        }
    } while (numbits);

    return bitswritten;
}



/*========================================================================*
 =                Routines to maintain an LZW-string table                =
 *========================================================================*/

/*-------------------------------------------------------------------------
 *
 *  NAME          FreeStrtab
 *
 *  DESCRIPTION   Free arrays used in string table routines
 */
static void
FreeStrtab(void)
{
    if (StrHsh) {
        free(StrHsh);
        StrHsh = NULL;
    }
    if (StrNxt) {
        free(StrNxt);
        StrNxt = NULL;
    }
    if (StrChr) {
        free(StrChr);
        StrChr = NULL;
    }
}



/*-------------------------------------------------------------------------
 *
 *  NAME          AllocStrtab
 *
 *  DESCRIPTION   Allocate arrays used in string table routines
 *
 *  RETURNS       GIF_OK     - OK
 *                GIF_OUTMEM - Out of memory
 */
static int
AllocStrtab(void)
{
    /* just in case */
    FreeStrtab();

    if ((StrChr = (Byte *) malloc(MAXSTR * sizeof(Byte))) == 0) {
        FreeStrtab();
        return GIF_OUTMEM;
    }
    if ((StrNxt = (Word *) malloc(MAXSTR * sizeof(Word))) == 0) {
        FreeStrtab();
        return GIF_OUTMEM;
    }
    if ((StrHsh = (Word *) malloc(HASHSIZE * sizeof(Word))) == 0) {
        FreeStrtab();
        return GIF_OUTMEM;
    }
    return GIF_OK;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          AddCharString
 *
 *  DESCRIPTION   Add a string consisting of the string of index plus
 *                the byte b.
 *
 *                If a string of length 1 is wanted, the index should
 *                be 0xFFFF.
 *
 *  INPUT         index   index to first part of string, or 0xFFFF is
 *                        only 1 byte is wanted
 *                b       last byte in new string
 *
 *  RETURNS       Index to new string, or 0xFFFF if no more room
 */
static Word
AddCharString(Word index, Byte b)
{
    Word hshidx;

    /* check if there is more room */
    if (NumStrings >= MAXSTR)
        return 0xFFFF;

    /* search the string table until a free position is found */
    hshidx = HASH(index, b);
    while (StrHsh[hshidx] != 0xFFFF)
        hshidx = (hshidx + HASHSTEP) % HASHSIZE;

    /* insert new string */
    StrHsh[hshidx] = NumStrings;
    StrChr[NumStrings] = b;
    StrNxt[NumStrings] = (index != 0xFFFF) ? index : NEXT_FIRST;

    return NumStrings++;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          FindCharString
 *
 *  DESCRIPTION   Find index of string consisting of the string of index
 *                plus the byte b.
 *
 *                If a string of length 1 is wanted, the index should
 *                be 0xFFFF.
 *
 *  INPUT         index   index to first part of string, or 0xFFFF is
 *                        only 1 byte is wanted
 *                b       last byte in string
 *
 *  RETURNS       Index to string, or 0xFFFF if not found
 */
static Word
FindCharString(Word index, Byte b)
{
    Word hshidx, nxtidx;

    /* check if index is 0xFFFF. in that case we need only return b,
     * since all one-character strings has their bytevalue as their
     * index */
    if (index == 0xFFFF)
        return b;

    /* search the string table until the string is found, or we find
     * HASH_FREE. in that case the string does not exist. */
    hshidx = HASH(index, b);
    while ((nxtidx = StrHsh[hshidx]) != 0xFFFF) {
        if (StrNxt[nxtidx] == index && StrChr[nxtidx] == b)
            return nxtidx;
        hshidx = (hshidx + HASHSTEP) % HASHSIZE;
    }

    /* no match is found */
    return 0xFFFF;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          ClearStrtab
 *
 *  DESCRIPTION   Mark the entire table as free, enter the 2**codesize
 *                one-byte strings, and reserve the RES_CODES reserved
 *                codes.
 *
 *  INPUT         codesize
 *                        number of bits to encode one pixel
 */
static void
ClearStrtab(int codesize)
{
    int q, w;
    Word *wp;

    /* no strings currently in the table */
    NumStrings = 0;

    /* mark entire hashtable as free */
    wp = StrHsh;
    for (q = 0; q < HASHSIZE; q++)
        *wp++ = HASH_FREE;

    /* insert 2**codesize one-character strings, and reserved codes */
    w = (1 << codesize) + RES_CODES;
    for (q = 0; q < w; q++)
        AddCharString(0xFFFF, q);
}



/*========================================================================*
 =                        LZW compression routine                         =
 *========================================================================*/

/*-------------------------------------------------------------------------
 *
 *  NAME          LZW_Compress
 *
 *  DESCRIPTION   Perform LZW compression as specified in the
 *                GIF-standard.
 *
 *  INPUT         codesize
 *                         number of bits needed to represent
 *                         one pixelvalue.
 *                inputbyte
 *                         function that fetches each byte to compress.
 *                         must return -1 when no more bytes.
 *
 *  RETURNS       GIF_OK     - OK
 *                GIF_OUTMEM - Out of memory
 */
static int
LZW_Compress(int codesize, int (*inputbyte)(void))
{
    register int c;
    register Word index;
    int  clearcode, endofinfo, numbits, limit, errcode;
    Word prefix = 0xFFFF;

    /* set up the given outfile */
    InitBitFile();

    /* set up variables and tables */
    clearcode = 1 << codesize;
    endofinfo = clearcode + 1;

    numbits = codesize + 1;
    limit = (1 << numbits) - 1;

    if ((errcode = AllocStrtab()) != GIF_OK)
        return errcode;
    ClearStrtab(codesize);

    /* first send a code telling the unpacker to clear the stringtable */
    WriteBits(clearcode, numbits);

    /* pack image */
    while ((c = inputbyte()) != -1) {
        /* now perform the packing. check if the prefix + the new
         *  character is a string that exists in the table */
        if ((index = FindCharString(prefix, c)) != 0xFFFF) {
            /* the string exists in the table. make this string the
             * new prefix.  */
            prefix = index;
        } else {
            /* the string does not exist in the table. first write
             * code of the old prefix to the file. */
            WriteBits(prefix, numbits);

            /* add the new string (the prefix + the new character) to
             * the stringtable */
            if (AddCharString(prefix, c) > limit) {
                if (++numbits > 12) {
                    WriteBits(clearcode, numbits - 1);
                    ClearStrtab(codesize);
                    numbits = codesize + 1;
                }
                limit = (1 << numbits) - 1;
            }

            /* set prefix to a string containing only the character
             * read. since all possible one-character strings exists
             * int the table, there's no need to check if it is found. */
            prefix = c;
        }
    }

    /* end of info is reached. write last prefix. */
    if (prefix != 0xFFFF)
        WriteBits(prefix, numbits);

    /* erite end of info -mark, flush the buffer, and tidy up */
    WriteBits(endofinfo, numbits);
    ResetOutBitFile();
    FreeStrtab();

    return GIF_OK;
}



/*========================================================================*
 =                              Other routines                            =
 *========================================================================*/

/*-------------------------------------------------------------------------
 *
 *  NAME          BitsNeeded
 *
 *  DESCRIPTION   Calculates number of bits needed to store numbers
 *                between 0 and n - 1
 *
 *  INPUT         n       number of numbers to store (0 to n - 1)
 *
 *  RETURNS       Number of bits needed
 */
static int
BitsNeeded(Word n)
{
    int ret = 1;

    if (!n--)
        return 0;
    while (n >>= 1)
        ++ret;
    return ret;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          InputByte
 *
 *  DESCRIPTION   Get next pixel from image. Called by the
 *                LZW_Compress()-function
 *
 *  RETURNS       Next pixelvalue, or -1 if no more pixels
 */
static int
InputByte(void)
{
    int ret;

    if (RelPixY >= ImageHeight)
        return -1;
    ret = GetPixel(ImageLeft + RelPixX, ImageTop + RelPixY);
    if (++RelPixX >= ImageWidth) {
        RelPixX = 0;
        ++RelPixY;
    }
    return ret;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          WriteScreenDescriptor
 *
 *  DESCRIPTION   Output a screen descriptor to the current GIF-file
 *
 *  INPUT         sd      pointer to screen descriptor to output
 *
 *  RETURNS       GIF_OK       - OK
 *                GIF_ERRWRITE - Error writing to the file
 */
static int
WriteScreenDescriptor(ScreenDescriptor *sd)
{
    Byte tmp;

    if (WriteWord(sd->LocalScreenWidth) != GIF_OK)
        return GIF_ERRWRITE;
    if (WriteWord(sd->LocalScreenHeight) != GIF_OK)
        return GIF_ERRWRITE;
    tmp = (sd->GlobalColorTableFlag << 7)
          | (sd->ColorResolution << 4)
          | (sd->SortFlag << 3)
          | sd->GlobalColorTableSize;
    if (WriteByte(tmp) != GIF_OK)
        return GIF_ERRWRITE;
    if (WriteByte(sd->BackgroundColorIndex) != GIF_OK)
        return GIF_ERRWRITE;
    if (WriteByte(sd->PixelAspectRatio) != GIF_OK)
        return GIF_ERRWRITE;

    return GIF_OK;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          WriteTransparentColorIndex (added by j.forkosh)
 *
 *  DESCRIPTION   Output a graphic extension block setting transparent
 *		  colormap index
 *
 *  INPUT         colornum       colormap index of color to be transparent
 *
 *  RETURNS       GIF_OK       - OK
 *                GIF_ERRWRITE - Error writing to the file
 */
static int
WriteTransparentColorIndex(int colornum)
{
    if ( colornum < 0 ) return GIF_OK;		/*no transparent color set*/
    if (WriteByte((Byte)(0x21)) != GIF_OK)	/*magic:Extension Introducer*/
        return GIF_ERRWRITE;
    if (WriteByte((Byte)(0xf9)) != GIF_OK)     /*magic:Graphic Control Label*/
        return GIF_ERRWRITE;
    if (WriteByte((Byte)(4)) != GIF_OK)		/* #bytes in block */
        return GIF_ERRWRITE;
    if (WriteByte((Byte)(1)) != GIF_OK)        /*transparent index indicator*/
        return GIF_ERRWRITE;
    if (WriteWord((Word)(0)) != GIF_OK)		/* delay time */
        return GIF_ERRWRITE;
    if (WriteByte((Byte)(colornum)) != GIF_OK)	/* transparent color index */
        return GIF_ERRWRITE;
    if (WriteByte((Byte)(0)) != GIF_OK)        /* terminator */
        return GIF_ERRWRITE;

    return GIF_OK;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          WriteImageDescriptor
 *
 *  DESCRIPTION   Output an image descriptor to the current GIF-file
 *
 *  INPUT         id      pointer to image descriptor to output
 *
 *  RETURNS       GIF_OK       - OK
 *                GIF_ERRWRITE - Error writing to the file
 */
static int
WriteImageDescriptor(ImageDescriptor *id)
{
    Byte tmp;

    if (WriteByte(id->Separator) != GIF_OK)
        return GIF_ERRWRITE;
    if (WriteWord(id->LeftPosition) != GIF_OK)
        return GIF_ERRWRITE;
    if (WriteWord(id->TopPosition) != GIF_OK)
        return GIF_ERRWRITE;
    if (WriteWord(id->Width) != GIF_OK)
        return GIF_ERRWRITE;
    if (WriteWord(id->Height) != GIF_OK)
        return GIF_ERRWRITE;
    tmp = (id->LocalColorTableFlag << 7)
          | (id->InterlaceFlag << 6)
          | (id->SortFlag << 5)
          | (id->Reserved << 3)
          | id->LocalColorTableSize;
    if (WriteByte(tmp) != GIF_OK)
        return GIF_ERRWRITE;

    return GIF_OK;
}



/**************************************************************************
 *                                                                        *
 *                    P U B L I C    F U N C T I O N S                    *
 *                                                                        *
 **************************************************************************/

/*-------------------------------------------------------------------------
 *
 *  NAME          GIF_Create
 *
 *  DESCRIPTION   Create a GIF-file, and write headers for both screen
 *                and image.
 *
 *  INPUT         filename
 *                        name of file to create (including extension)
 *                width   number of horisontal pixels on screen
 *                height  number of vertical pixels on screen
 *                numcolors
 *                        number of colors in the colormaps
 *                colorres
 *                        color resolution. Number of bits for each
 *                        primary color
 *
 *  RETURNS       GIF_OK        - OK
 *                GIF_ERRCREATE - Couldn't create file
 *                GIF_ERRWRITE  - Error writing to the file
 *                GIF_OUTMEM    - Out of memory allocating color table
 */
int
GIF_Create(const char *filename, int width, int height,
	   int numcolors, int colorres)
{
    int q, tabsize;
    Byte *bp;
    ScreenDescriptor SD;

    /* initiate variables for new GIF-file */
    NumColors = numcolors ? (1 << BitsNeeded(numcolors)) : 0;
    BitsPrPrimColor = colorres;
    ScreenHeight = height;
    ScreenWidth = width;

    /* create file specified */
    if (Create(filename) != GIF_OK)
        return GIF_ERRCREATE;

    /* write GIF signature */
    if ((Write("GIF87a", 6)) != GIF_OK)
        return GIF_ERRWRITE;

    /* initiate and write screen descriptor */
    SD.LocalScreenWidth = width;
    SD.LocalScreenHeight = height;
    if (NumColors) {
        SD.GlobalColorTableSize = BitsNeeded(NumColors) - 1;
        SD.GlobalColorTableFlag = 1;
    } else {
        SD.GlobalColorTableSize = 0;
        SD.GlobalColorTableFlag = 0;
    }
    SD.SortFlag = 0;
    SD.ColorResolution = colorres - 1;
    SD.BackgroundColorIndex = 0;
    SD.PixelAspectRatio = 0;
    if (WriteScreenDescriptor(&SD) != GIF_OK)
        return GIF_ERRWRITE;

    /* allocate color table */
    if (ColorTable) {
        free(ColorTable);
        ColorTable = NULL;
    }
    if (NumColors) {
        tabsize = NumColors * 3;
        if ((ColorTable = (Byte *) malloc(tabsize * sizeof(Byte))) == NULL)
            return GIF_OUTMEM;
        else {
            bp = ColorTable;
            for (q = 0; q < tabsize; q++)
                *bp++ = 0;
        }
    }
    return 0;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          GIF_SetColor
 *
 *  DESCRIPTION   Set red, green and blue components of one of the
 *                colors. The color components are all in the range
 *                [0, (1 << BitsPrPrimColor) - 1]
 *
 *  INPUT         colornum
 *                        color number to set. [0, NumColors - 1]
 *                red     red component of color
 *                green   green component of color
 *                blue    blue component of color
 */
void
GIF_SetColor(int colornum, int red, int green, int blue)
{
    long maxcolor;
    Byte *p;

    maxcolor = (1L << BitsPrPrimColor) - 1L;
    p = ColorTable + colornum * 3;
    *p++ = (Byte) ((red * 255L) / maxcolor);
    *p++ = (Byte) ((green * 255L) / maxcolor);
    *p++ = (Byte) ((blue * 255L) / maxcolor);
}



/*-------------------------------------------------------------------------
 *
 *  NAME          GIF_SetTransparent (added by j.forkosh)
 *
 *  DESCRIPTION   Set colormap index of color to be transparent
 *
 *  INPUT         colornum
 *                        color number to set transparent. [0, NumColors - 1]
 */
void
GIF_SetTransparent(int colornum)
{
    TransparentColorIndex = colornum;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          GIF_CompressImage
 *
 *  DESCRIPTION   Compress an image into the GIF-file previousely
 *                created using GIF_Create(). All color values should
 *                have been specified before this function is called.
 *
 *                The pixels are retrieved using a user defined callback
 *                function. This function should accept two parameters,
 *                x and y, specifying which pixel to retrieve. The pixel
 *                values sent to this function are as follows:
 *
 *                    x : [ImageLeft, ImageLeft + ImageWidth - 1]
 *                    y : [ImageTop, ImageTop + ImageHeight - 1]
 *
 *                The function should return the pixel value for the
 *                point given, in the interval [0, NumColors - 1]
 *
 *  INPUT         left    screen-relative leftmost pixel x-coordinate
 *                        of the image
 *                top     screen-relative uppermost pixel y-coordinate
 *                        of the image
 *                width   width of the image, or -1 if as wide as
 *                        the screen
 *                height  height of the image, or -1 if as high as
 *                        the screen
 *                getpixel
 *                        address of user defined callback function.
 *                        (see above)
 *
 *  RETURNS       GIF_OK       - OK
 *                GIF_OUTMEM   - Out of memory
 *                GIF_ERRWRITE - Error writing to the file
 */
int
GIF_CompressImage(int left, int top, int width, int height,
		  int (*getpixel)(int x, int y))
{
    int codesize, errcode;
    ImageDescriptor ID;

    if (width < 0) {
        width = ScreenWidth;
        left = 0;
    }
    if (height < 0) {
        height = ScreenHeight;
        top = 0;
    }
    if (left < 0)
        left = 0;
    if (top < 0)
        top = 0;

    /* write global colortable if any */
    if (NumColors)
        if ((Write(ColorTable, NumColors * 3)) != GIF_OK)
            return GIF_ERRWRITE;

    /* write graphic extension block with transparent color index */
    if ( TransparentColorIndex >= 0 )     /* (added by j.forkosh) */
      if ( WriteTransparentColorIndex(TransparentColorIndex)
      !=   GIF_OK ) return GIF_ERRWRITE;

    /* initiate and write image descriptor */
    ID.Separator = ',';
    ID.LeftPosition = ImageLeft = left;
    ID.TopPosition = ImageTop = top;
    ID.Width = ImageWidth = width;
    ID.Height = ImageHeight = height;
    ID.LocalColorTableSize = 0;
    ID.Reserved = 0;
    ID.SortFlag = 0;
    ID.InterlaceFlag = 0;
    ID.LocalColorTableFlag = 0;

    if (WriteImageDescriptor(&ID) != GIF_OK)
        return GIF_ERRWRITE;

    /* write code size */
    codesize = BitsNeeded(NumColors);
    if (codesize == 1)
        ++codesize;
    if (WriteByte(codesize) != GIF_OK)
        return GIF_ERRWRITE;

    /* perform compression */
    RelPixX = RelPixY = 0;
    GetPixel = getpixel;
    if ((errcode = LZW_Compress(codesize, InputByte)) != GIF_OK)
        return errcode;

    /* write terminating 0-byte */
    if (WriteByte(0) != GIF_OK)
        return GIF_ERRWRITE;

    return GIF_OK;
}



/*-------------------------------------------------------------------------
 *
 *  NAME          GIF_Close
 *
 *  DESCRIPTION   Close the GIF-file
 *
 *  RETURNS       GIF_OK       - OK
 *                GIF_ERRWRITE - Error writing to file
 */
int
GIF_Close(void)
{
    ImageDescriptor ID;

    /* initiate and write ending image descriptor */
    ID.Separator = ';';
    ID.LeftPosition = 0;	/* (added by j.forkosh) */
    ID.TopPosition = 0;		/* " initialize entire ID structure */
    ID.Width = 0;		/* " and ditto for other ID.x=0; below */
    ID.Height = 0;
    ID.LocalColorTableSize = 0;
    ID.Reserved = 0;
    ID.SortFlag = 0;
    ID.InterlaceFlag = 0;
    ID.LocalColorTableFlag = 0;

    if (WriteImageDescriptor(&ID) != GIF_OK)
        return GIF_ERRWRITE;

    /* close file */
    Close();

    /* release color table */
    if (ColorTable) {
        free(ColorTable);
        ColorTable = NULL;
    }

    return GIF_OK;
}
/* --- end-of-file gifsave.c --- */
