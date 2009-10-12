<?php
if(preg_match('/.*search\.htm/',$_SERVER['PHP_SELF'])){
  header('location:searchit.php');
}
?>
<!--

    This is default template file for mnoGoSearch 3.2
    (C) 1999-2002, mnoGoSearch developers team <devel@mnogosearch.org>

    Please rename to search.htm and edit as desired.
    See doc/README.templates for detailed information.
    You may want to keep the original file for future reference.

    WARNING: Use proper chmod to protect your passwords!
-->

<!--variables
# Database parameters are to be used with SQL backend
# and do not matter for built-in text files support
# Format (for mnogo-3.2.4 and below): <DBType>:[//[DBUser[:DBPass]@]DBHost[:DBPort]]/DBName/
# Format (for mnogo-3.2.5+): <DBType>:[//[DBUser[:DBPass]@]DBHost[:DBPort]]/DBName/[?dbmode=mode]
DBAddr  mysql://db_user:db_pass@db_host/db_name/?dbmode=single

# Uncomment this line to enable search result cache
#Cache yes

# Uncomment this line if you want to detect and show clones
#DetectClones yes

# Use proper local and browser charsets
# Examples:

LocalCharset	iso-8859-1
BrowserCharset	iso-8859-1

#LocalCharset	utf-8
#BrowserCharset	utf-8

#LocalCharset   koi8-r
#BrowserCharset koi8-r

# For cache mode and built-in database
# you may choose alternative working directory
#VarDir /usr/local/mnogosearch/var

# Load stopwords file.  File name is either absolute
# or relative to /etc directory of mnoGoSearch installation.
#
#StopwordFile stopwords/en.sl
#StopwordFile stopwords/ru.sl
#

#IspellUsePrefixes yes/no

# a newer mnogosearch-3.2.x ispell affix commands
#Affix en us-ascii /opt/udm/ispell/en.aff
#Affix ru koi8-r /opt/udm/ispell/ru.aff
#Spell en us-ascii /opt/udm/ispell/en.dict
#Spell ru koi8-r /opt/udm/ispell/ru.dict

# Word lengths
MinWordLength 1
MaxWordLength 32

#
# How to hilight searched words.
#
#HlBeg	<font color="000088"><b>
#HlEnd	</b></font>
HlBeg <hl>
HlEnd </hl>

# Load synonyms file.  File name is either absolute
# or relative to /etc directory of mnoGoSearch installation.
#
#Synonym /opt/udm/synonym/english.syn
#Synonym /opt/udm/synonym/russian.syn
#Synonym /opt/udm/synonym/francais.syn

#Alias <find-prefix> <replace-prefix>
#Alias http://localhost/ http://server.domain/

# Grouping results by site (requires mnogosearch-3.2.7+)
# GroupBySite yes

# Uncoment this line to change default maximal excerpt size. Default value is 256
# (requires mnogosearch-3.2.16+)
ExcerptSize 512
#

# Uncomment this line to change number of characters before and after search words
# in excerpts. Default value is 40.
# (requires mnogosearch-3.2.16+)
ExcerptPadding 128
#

# Uncomment this line to change Last-Modified format output
# Use strftime function meta variables
#DateFormat %d %b %Y %X %Z

# Uncomment to limit maximum number of results
ResultsLimit 1000

# Uncomment this line if you want to generate misspelled
# search word suggestions. You need to run "indexer -Ewrdstat"
# before using this feature.
#
#Suggest yes

#-------------------------------------------------
# obsolete (mnogosearch-3.1.x or 3.2 old versions specific parameters)

# Choose storage mode (for mnogosearch-3.2.4 or earlier):
#DBMode  single
#DBMode  multi
#DBMode  crc
#DBMode  crc-multi
#DBMode  cache

# Uncomment this line to enable query tracking facility
# Use trackquery parameter in DBAddr command if using with mnogosearch-3.2.13+
#TrackQuery yes

#IspellMode text

# Uncomment if index was built with phrase support
#Phrase yes

# Load stopwords from SQL table
#StopwordTable stopword

# old mnogosearch-3.1.x ispell affix commands
#Affix en /opt/udm/ispell/en.aff
#Affix ru /opt/udm/ispell/ru.aff
#Spell en /opt/udm/ispell/en.dict
#Spell ru /opt/udm/ispell/ru.dict

# Searchd address - only for mnogosearch - 3.2.3 or earlier !
#SearchdAddr localhost

# Uncomment this line to enable document presence check at stored
# (not used with mnogosearch-3.2.16+)
#StoredAddr localhost

# URL basis for storedoc.cgi
# (not used with mnogosearch-3.2.16+)
#StoredocURL     /cgi-bin/storedoc.cgi

-->

<!--top-->
<results>
<!--/top-->

<!--stored-->
<cached>
	<cached_href>$(storef_href)</cached_href>
</cached>
<!--/stored-->

<!--site_limit-->
<more_results_from_this_site>$(sitelimit_href)</more_results_from_this_site>
<limit_per_site>$(PerSite)</limit_per_site>
<!--/site_limit-->

<!--clone-->
<clone>
	<clone_du>$DU</clone_du>
	<clone_dc>$DC</clone_dc>
	<clone_dm>$DM</clone_dm>
	<clone_ds_byte>$DS</clone_ds_byte>
</clone>
<!--/clone-->

<!--restop-->
<search_info>$W</search_info>
<search_term>$(WS)</search_term>
<query>$Q</query>
<num_found>$t</num_found>
<search_time>$SearchTime</search_time>
<!--/restop-->

<!--res-->
<result>
	<result_dn>$DN</result_dn>
	<result_du>$DU</result_du>
	<result_dt>$DT</result_dt>
	<result_dr>$DR</result_dr>
	<result_pop_rank>$(Pop_Rank)</result_pop_rank>
	<result_de>$DE</result_de>
	<result_dx>$DX</result_dx>
	<result_dud>$DUD</result_dud>
	<result_dc>($DC)</result_dc>
	<result_dm>$DM</result_dm>
	<result_ds>$DS</result_ds>
	<result_cl>$CL</result_cl>
	<result_stored>$(STORED)</result_stored>
	<result_sitelimit>$(SITELIMIT)</result_sitelimit>
</result>
<!--/res-->

<!--ftpres-->
<result>
	<result_dn>$DN</result_dn>
	<result_du>$DU</result_du>
	<result_dt>$DT</result_dt>
	<result_dr>$DR</result_dr>
	<result_pop_rank>$(Pop_Rank)</result_pop_rank>
	<result_de>$DE</result_de>
	<result_dx>$DX</result_dx>
	<result_dud>$DUD</result_dud>
	<result_dc>($DC)</result_dc>
	<result_dm>$DM</result_dm>
	<result_ds>$DS</result_ds>
	<result_cl>$CL</result_cl>
	<result_stored>$(STORED)</result_stored>
	<result_sitelimit>$(SITELIMIT)</result_sitelimit>
</result>
<!--/ftpres-->

<!--resbot-->
<result_v>$V</result_v>
<!--/resbot-->





<!--restop-->
<search_info>$W</search_info>
<search_term>$(WS)</search_term>
<query>$Q</query>
<num_found>$t</num_found>
<search_time>$SearchTime</search_time>
<!--/restop-->
<!--res-->
<result>
	<result_dn>$DN</result_dn>
	<result_du>$DU</result_du>
	<result_dt>$DT</result_dt>
	<result_dr>$DR</result_dr>
	<result_pop_rank>$(Pop_Rank)</result_pop_rank>
	<result_de>$DE</result_de>
	<result_dx>$DX</result_dx>
	<result_dud>$DUD</result_dud>
	<result_dc>($DC)</result_dc>
	<result_dm>$DM</result_dm>
	<result_ds>$DS</result_ds>
	<result_cl>$CL</result_cl>
	<result_stored>$(STORED)</result_stored>
	<result_sitelimit>$(SITELIMIT)</result_sitelimit>
</result>
<!--/res-->
<!--resbot-->
<result_v>$V</result_v>
<!--/resbot-->



<!--restop-->
<search_info>$W</search_info>
<search_term>$(WS)</search_term>
<query>$Q</query>
<num_found>$t</num_found>
<search_time>$SearchTime</search_time>
<!--/restop-->
<!--res-->
<result>
	<result_dn>$DN</result_dn>
	<result_du>$DU</result_du>
	<result_dt>$DT</result_dt>
	<result_dr>$DR</result_dr>
	<result_pop_rank>$(Pop_Rank)</result_pop_rank>
	<result_de>$DE</result_de>
	<result_dx>$DX</result_dx>
	<result_dud>$DUD</result_dud>
	<result_dc>($DC)</result_dc>
	<result_dm>$DM</result_dm>
	<result_ds>$DS</result_ds>
	<result_cl>$CL</result_cl>
	<result_stored>$(STORED)</result_stored>
	<result_sitelimit>$(SITELIMIT)</result_sitelimit>
</result>
<!--/res-->
<!--resbot-->
<result_v>$V</result_v>
<!--/resbot-->




<!--clone-->
<clone>
	<clone_du>$DU</clone_du>
	<clone_dud>$DUD</clone_dud>
	<clone_dc>$DC</clone_dc>
	<clone_dm>$DM</clone_dm>
	<clone_ds_byte>$DS</clone_ds_byte>
</clone>
<!--/clone-->

<!--navigator-->
<navigator>
	<navigator_nl>$NL</navigator_nl>
	<navigator_nb>$NB</navigator_nb>
	<navigator_nr>$NR</navigator_nr>
</navigator>
<!--/navigator-->

<!--navleft-->
<navleft>
	<navleft_nh>$NH</navleft_nh>
</navleft>
<!--/navleft-->

<!--navleft_nop-->
<!--/navleft_nop-->

<!--navbar1-->
<navbar1>
	<navbar1_nh>$NH</navbar1_nh>
	<navbar1_np>$NP</navbar1_np>
</navbar1>
<!--/navbar1-->

<!--navbar0-->
<navbar0>
	<navbar0_np>$NP</navbar0_np>
</navbar0>
<!--/navbar0-->

<!--navright-->
<navright>
	<navright_nh>$NH</navright_nh>
</navright>
<!--/navright-->

<!--navright_nop-->
<!--/navright_nop-->


<!--notfound-->
<notfound>
	<notfound_search_time>$SearchTime</notfound_search_time>
</notfound>
<!--/notfound-->

<!--error-->
<error>
	<error_msg>$E</error_msg>
</error>
<!--/error-->


<!--noquery-->
<noquery>
	<noquery_msg>No search query</noquery_msg>
</noquery>
<!--/noquery-->


<!--bottom-->
</results>
<!--/bottom-->

<!--storedoc_top-->
<storedoc>
	<storedoc_url>$(URL)</storedoc_url>
	<storedoc_document_id>$(ID)</storedoc_document_id>
	<storedoc_last_modified>$(Last-Modified)</storedoc_last_modified>
	<storedoc_language>$(Content-Language)</storedoc_language>
	<storedoc_charset>$(Charset)</storedoc_charset>
	<storedoc_size_bytes>$(Content_Length)</storedoc_size_bytes>
<!--/storedoc_top-->

<!--storedoc-->
	<storedoc_content>$(document)</storedoc_content>
<!--/storedoc-->

<!--storedoc_bottom-->
</storedoc>
<!--/storedoc_bottom-->
