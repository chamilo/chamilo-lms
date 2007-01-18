function sprintf2(arg) {
 if( arg.length < 2 ) {
  return arg[0];
 }
 var data = arg[ 0 ];
 for( var k=1; k<arg.length; ++k ) {
  switch( typeof( arg[ k ] ) )
  {
   case 'string':
    data = data.replace( /%s/, arg[ k ] );
    break;
   case 'number':
    data = data.replace( /%d/, arg[ k ] );
    break;
   case 'boolean':
    data = data.replace( /%b/, arg[ k ] ? 'true' : 'false' );
    break;
   default:
    /// function | object | undefined
    break;
  }
 }
 return( data );
}
if( !String.sprintf2 ) {
 String.sprintf2 = sprintf2;
}