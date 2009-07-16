// Register the command.

var FCKGlossary = function( name )
{
  this.Name = name ;
  this.StyleName = '_FCK_Glossary' ;
  this.IsActive = false ; 
  this.EditMode = FCK.EditMode;
        
  FCKStyles.AttachStyleStateChange( this.StyleName, this._OnStyleStateChange, this ) ;
}

FCKGlossary.prototype =
 {
	Execute : function()
	{
		FCKUndo.SaveUndoStep() ;

		if ( this.IsActive ) {		    	    
			FCKStyles.RemoveStyle(this.StyleName) ;									
		}
		else {		    		    
			FCKStyles.ApplyStyle( this.StyleName ) ;										
		}
		FCK.Focus() ;
		FCK.Events.FireEvent( 'OnSelectionChange' ) ;
	},

	GetState : function()
	{
		if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG )
			return FCK_TRISTATE_DISABLED ;							
		return this.IsActive ? FCK_TRISTATE_ON : FCK_TRISTATE_OFF;
	},

	_OnStyleStateChange : function( styleName, isActive )
	{
		this.IsActive = isActive ;
	}
 };




FCKCommands.RegisterCommand( 'Glossary' , new FCKGlossary( 'Glossary' ) ) ;

/*FCKCommands.RegisterCommand( 'Glossary',
	new FCKCoreStyleCommand( 'Italic' )
) ;*/

// Create and register the Audio toolbar button.
var oGlossaryItem = new FCKToolbarButton( 'Glossary', FCKLang['GlossaryTitle'], null, null, null, null, null );

oGlossaryItem.IconPath	= FCKConfig.PluginsPath + 'glossary/glossary.gif' ;

 FCKToolbarItems.RegisterItem( 'Glossary', oGlossaryItem ) ;
