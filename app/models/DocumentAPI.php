<?php
/**
 * Used to handle an LRSs 3 document APIs.
 *
 **/

use Jenssegers\Mongodb\Model as Eloquent;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentAPI extends Eloquent {

  /**
   * Our MongoDB collection used by the model.
   *
   * @var string
   */
  protected $collection = 'documentapi';

  /**
   * Hidden values we don't return
   *
   * @var array
   *
   **/
  protected $hidden = array('_id', 'created_at', 'updated_at', 'lrs', 'apitype');


  /**
   * Handle content storage
   * @param Mixed $content          The content passed in the request
   */
  public function setContent( $content, $contentType, $method){

    switch( $contentType ){
      case "application/json":

        $request_content = json_decode($content, TRUE);
        if( is_object( $request_content ) ){ //check that the content type of the request matches the content
          \App::abort(400, 'JSON detected without a correct Content-Type sent');
        }

        if( !$this->exists ){ //if we are adding a new piece of content...
          $this->content      = $request_content;
        } else if( $this->contentType === $contentType ){ //if existing content, check that it is also JSON
          switch( $method ){
            case 'PUT': //overwrite content
              $this->content = $request_content;
            break;
            case 'POST': //merge variables
              $this->content = array_merge( $this->content, $request_content );
            break;
            default:
              \App::abort( 400, 'Only PUT AND POST methods may amend content');
            break;
          }
        } else { //reject updating with non JSON content
          \App::abort(400, 'JSON document content may not be overwritten with that of another type');
        }
      break;

      case "text/plain":
        if( !$this->exists ){
          $this->content = $content;
        } else {
          \App::abort(400, sprintf('Cannot amend existing %s document with a string', $this->contentType) );
        }
      break;

      default:
        if( !$this->exists ){ // check we are adding a new document
          //HANDLE FILE SAVES???
        } else {
          \App::abort(400, sprintf('Cannot amend existing %s document with a file', $this->contentType) );
        }
      break;
    }
    
    $this->contentType  = $contentType;

  }

  public function getContentDir(){
    return base_path().'/uploads/'.$this->lrs.'/documents/';
  }

  public function getFilePath(){
    return $this->getContentDir() + $this->content;
  }

}