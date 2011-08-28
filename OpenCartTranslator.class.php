<?php

/*
PHP-CLI Class to help OpenCart translation
@author Istvan Dobrentei, http://dobrenteiistvan.hu
*/

class OpenCartTranslator
{

    private  $fromLanguage = '';
    private  $toLanguage = '';
    private  $fromPath = '';
    private  $toPath = '';
    private  $newFiles;
    private  $existingFiles;
    private  $newVersion;

    /**
     * __construct 
     * 
     * @param mixed $settings 
     * @access protected
     * @return void
     */
    function __construct($settings)
	{
	  	$this->checkVersion();
        $this->fromPath = $settings['pathFrom'];
        $this->toPath = $settings['pathTo'];
        $this->fromLanguage = $settings['fromLanguage'];
        $this->toLanguage = $settings['toLanguage'];
        $this->newVersion = $settings['newVersion'];
        $this->setFiles();
	    $this->createNewLangFiles();
    } 
    
    /*
      Set files variables
      newFiles doesn't exist and you have to create it first
      existingFiles contains module language arrays and you have to compare it with 
      the translatable language array 
    */
    /**
     * setFiles 
	 *
     * @access private
     * @return void
     */
    private function setFiles()
	{
          $newFiles = array();
          $oldFiles = array();
          $FROM_DIR_PATH = realpath($this->fromPath);
          $OTHER_DIR_PATH = realpath($this->toPath);
          $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($FROM_DIR_PATH), 
                                         			RecursiveIteratorIterator::SELF_FIRST
          );
          
          foreach($objects as $name => $object)
		  {
                $temp = str_replace($FROM_DIR_PATH, $OTHER_DIR_PATH, $name);
                $path = str_replace($this->fromLanguage, $this->toLanguage, $temp);
                /*Ha directory és nincs ilyen, megjegyezzük a dir path-t 
                Ez azt jelenti, hogy az adott modul teljesen új és egyáltalán nem létezik a fordítása
                Ha file és nem létezik szintén  megjegyezzük. => létrehozzuk üres tartalommal és majd később
                interaktívan kérdezzük meg a fordítást és kiírjuk. 
      
                Ha a file létezik, ez azt jelenti, hogy van fordítása. Ilyenkor a két tartalmat kell ellenőrizni.  
                A file tartalma egy tömb. A két tömb kulcsértékeit kell megkeresni. Ha nem 
                létezik akkor létrehozni az új nyelvvi tartalommal. Ezt kérjük be az inputról, majd
                kiírjuk a file végére, megjelölve, hogy a script írta ki. 
                */
                if((!is_file($path))&&(!is_dir($path)))
				{
                	array_push($newFiles, $path); 
                }
				elseif(!is_dir($name))
				{
                	array_push($oldFiles, $name); 
                }
          }
          $this->newFiles = $newFiles;
          $this->existingFiles = $oldFiles;
    }
    
    /**
     * translate
     * 
     * @access public
     * @return void
     */
    public function translate()
	{     
		$idx = 0;              //counter for file processing
	    $counter = 0;          //for diffArray
	    $diffArray = array();  //store the language key differencies
	    $toText = chr(13);     //the user inputted and translated text
	    $fileName = "";	       //appended translated text
	    $handle = null;        //file pointer
	    $_= array();           //array in the language files
	      
	//--------------------------
        if (count($this->existingFiles) != 0)
		{
          do
		  {
		  	if(count($diffArray) == 0)
			{
                require ($this->existingFiles[$idx]);
                $fromArray = $_;$_= array();
                $temp = str_replace(realpath($this->fromPath), 
                                realpath($this->toPath), $this->existingFiles[$idx]);
                $actPath = str_replace($this->fromLanguage, $this->toLanguage, $temp);
                require($actPath);
                $toArray = $_; $_ = array();
                $diffArray = array_diff_key($fromArray, $toArray);
		            /*If diffArray == 0 then increase idx variable else it increase after 
	               writing diffArray to file 		
		            */
		        if (count($diffArray) == 0)
				{
		        	$idx++;	//get next file
		        }
	         }

             if(count($diffArray) != 0)
			 {
		            if($counter == 0)
					{
		            	echo str_replace(realpath($this->fromPath), "", $this->existingFiles[$idx])."\n";
		            	$temp = str_replace(realpath($this->fromPath), 
                                realpath($this->toPath), $this->existingFiles[$idx]);
            	  		$actPath = str_replace($this->fromLanguage, $this->toLanguage, $temp);
		            	$fileName = $actPath;
		            	//-----> Open file and check writeability
		            	if(is_writable($fileName))
						{
			            	if(!$handle = fopen($fileName,'a'))
							{
				            	$this->writeLog('Cannot open the file:'.$fileName);
				            	exit;
			             	}
		            	}
						else
						{
			            	$this->writeLog('The file is not writeable ! '.$fileName);
			            	exit;
		            	}
		            }
		            //process difference
		            $diffArrayKeys = array_keys($diffArray);
	    	        $fromText = $diffArray[$diffArrayKeys[$counter]];
	    	        //translate or copy translateable -->
                	echo $fromText."\n";
	    	       	do
					{
                  	  $answer = $this->promptUser("Do you translate it? Y/N/Q");
	    	       	}
					while(($answer != "Y") && ($answer != "N") && ($answer != "Q") );
	    	        if($answer == "Y")
					{
                    		$toText = $this->promptUser($fromText."=>");      
                	}
					elseif($answer == "Q")
					{
                    		$toText = null;     
                	}else
					{
                    		$toText = $fromText;
                	}
					//Itt beteszek minden ' jel elé egy \ jelet 
              		//<-------------
		            if($toText != null)
					{
						if ($counter == 0)
						{
                      		$this->cutLine($fileName, -1);	//remove the last line
		               		$this->beginEntry($handle, $fileName);
		              	}
                  		$content = "\$_['".$diffArrayKeys[$counter]."'] = '".addcslashes($toText,"'")."';\n";
		              	$this->writeFile($handle, $content, $fileName);
		              	if($counter == count($diffArray)-1)
						{
		                	$diffArray = array();
		                	$counter = 0;
		                	if($handle)
							{
			                	$this->endEntry($handle, $fileName);
                       			fclose($handle);
			                 	$idx++;	
		                	}
		              	}
		              	else
						{
		                	$counter++;	
		              	}
		            }
	         }
          }while(($toText != null)&&($idx != count($this->existingFiles)));	//end loop if condition is FALSE
        }//end if
	
        if($toText == null)
		{
           if($handle && ($counter != 0))
		   {
				$this->endEntry($handle, $fileName);	
		   }
		   fclose($handle);
	       $this->writeLog("The translation is NOT COMPELTED yet, please run the script again !");
		}else
		{
	         $this->writeLog("Well done! The translation is completed! Bye!");
    	}
		echo "If you have any question or any recommendation, please contact me.\nWEB: http://dobrenteiistvan.hu\n";
	    exit;
    }
    
    
    /**
     * promptUser  
     * 
     * @param mixed $promptStr 
     * @param mixed $defaultVal 
     * @access private
     * @return void
     */
    private function promptUser($promptStr,$defaultVal=false)
	{

        if($defaultVal) 
		{                             // If a default set
          echo $promptStr. "[". $defaultVal. "] : "; // print prompt and default
        } 
        else 
		{                                        // No default set
          echo $promptStr. ": ";                     // print prompt only
        } 
        $name = chop(fgets(STDIN));                   // Read input. Remove CR
        if(empty($name)) 
		{                            // No value. Enter was pressed
          return $defaultVal;                        // return default
        }
        else 
		{                                        // Value entered
          return $name;                              // return value 
        }
    }

    /**
     * writeFile  
     * 
     * @param mixed $handle 
     * @param mixed $content 
     * @param mixed $filename 
     * @access private
     * @return void
     */
    private function writeFile($handle, $content, $filename)
	{
	     if (fwrite($handle, $content) === FALSE) 
		 {
        	$this->writeLog("Cannot write to file:". $filename);
        	exit;
    	 }
    }

    /*
	   because we want remove the last line from a php file.
    */
    /**
     * cutLine  
     * 
     * @param mixed $fileName 
     * @param mixed $line_no 
     * @access protected
     * @return void
     */
    protected function cutLine($fileName, $line_no = -1) 
	{

	       $strip_return = FALSE;
	       if(is_writable($fileName))
		   {
		          $data = file($fileName);
		          $pipe = fopen($fileName, 'w');
		          $size = count($data);
		          if($line_no == -1)
				  { 
	    		         $skip = $size-1;
		          }
		          else 
				  {
	    		         $skip = $line_no-1;
		          };

		          for($line = 0;$line < $size;$line ++)
				  {
	    	          if($line != $skip)
					  {
		 	                fputs($pipe,$data[$line]);
	    	          }
	    	          else
					  {
		 	                $strip_return = TRUE;
	    	          }
		          }
		          fclose($pipe);
	       }else
		   {
		          $this->writeLog('The file is not writeable ! '.$fileName);
	       }
	       
	       return $strip_return;
    } 
    
    /**
     * beginEntry  
     * 
     * @param mixed $handle 
     * @param mixed $fileName 
     * @access protected
     * @return void
     */
    protected function beginEntry($handle, $fileName)
	{
       $content  = "\n"; 
       $content .= "/* ### BEGIN Translation (";
       $content .= $this->newVersion;
       $content .= ") from script ";
       $content .= $this->getActTime()." ### */";
       $content .= "\n";           
       $this->writeFile($handle, $content, $fileName);
    }
    
    /**
     * endEntry  
     * 
     * @param mixed $handle 
     * @param mixed $fileName 
     * @access protected
     * @return void
     */
    protected function endEntry($handle, $fileName)
	{        
       $content  = "\n"; 
       $content .= "/* ### END Translation from script ";
       $content .= $this->getActTime()." ### */";
       $content .= "\n";
       $content .= chr(63).chr(62);        //php end tag
       $this->writeFile($handle, $content, $fileName);
    }
    
    /**
     * getActTime  
     * 
     * @access protected
     * @return void
     */
    protected function getActTime()
	{
        return date("F j, Y, g:i a");     
    }
    
    /**
     * createNewLangFiles  
     * 
     * @access private
     * @return void
     */
    private function createNewLangFiles()
	{
	     if(count($this->newFiles) != 0)
		 {
	         foreach($this->newFiles as $n)
			 {
		       	$this->writeLog("Created new file:".$n."\n");
		      	$file = fopen($n,"w");
			  	$content = chr(60).chr(63)."\n".chr(63).chr(62);
			  	$this->writeFile($file, $content, $n);
		        fclose($file);
	         }
	     }
    }

    /**
     * checkVersion  
     * 
     * @access protected
     * @return void
     */
    protected function checkVersion()
	{
	     if(version_compare(phpversion(),'5.1.0','<') == TRUE)
		 {
		      $this->writeLog('Minimum PHP5.1+ Required');		
		      exit;		
	     }
    }
	
    /**
     * writeLog  
     * 
     * @param mixed $Msg 
     * @access protected
     * @return void
     */
    protected function writeLog($Msg)
	{
	     print $Msg."\n";
    }
} 
?>
