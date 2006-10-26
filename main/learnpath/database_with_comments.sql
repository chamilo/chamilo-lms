----------------------------------------------------------------------------------------
For running, you have to remove the comments, no matter how useful they are !!! SOrry ;)
----------------------------------------------------------------------------------------

CREATE TABLE `learnpath_item` ( 	 //former learnpath
  `id` int(11) NOT NULL auto_increment,
  `chapter_id` int(11) default NULL,  //former module
  `item_type` varchar(50) default NULL,  //former resource_type
  `item_id` int(11) default NULL,        //former resource_id
  `display_order` int(11) default NULL,
  `title` varchar(255) default NULL,
  `description` text,  			 //former comment
  `prereq_id` int(11) default NULL,
  `prereq_type` char(1) default NULL,    //new field
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM

CREATE TABLE `learnpath_chapter` ( 		//former learnpath_categories
  `id` int(6) NOT NULL auto_increment,
  `learnpath_id` int(6) default NULL,           //new row : connecting chapter to path
  `chapter_name` varchar(255) default NULL,     //former categoryname
  `chapter_description` text,  			//former description
  `display_order` mediumint(8) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

CREATE TABLE `learnpath_main` ( 	//new table for creating more paths
  `learnpath_id` int(6) NOT NULL auto_increment,
  `learnpath_name` varchar(255) default NULL,
  `learnpath_description` text,
  `visibility` char(1) NOT NULL default 'i',
  PRIMARY KEY  (`learnpath_id`)
) TYPE=MyISAM

CREATE TABLE `learnpath_user` ( 	//new table for tracking
  `user_id` int(6) default NULL,
  `learnpath_id` int(6) default NULL,
  `learnpath_item_id` int(6) default NULL,
  `status` varchar(15) default NULL,
  `score` int(10) default NULL,
  `time` varchar(20) default NULL
) TYPE=MyISAM

----------------------------------------------------------------------------------------
For running, you have to remove the comments, no matter how useful they are !!! SOrry ;)
----------------------------------------------------------------------------------------
