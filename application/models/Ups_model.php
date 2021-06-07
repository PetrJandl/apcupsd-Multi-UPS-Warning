<?php

class Ups_model extends CI_Model {
    
    var $app;

    function __construct()
    {
        parent::__construct();
	$this->app = & get_instance();

    }

    public function readHostConf()
    {
                $txt_file    = file_get_contents('/etc/apcupsd/hosts.conf');
                $rows        = explode("\n", $txt_file);
                array_shift($rows);
                // projde config a prikazem zjisti statusy UPS z configu
                foreach($rows as $row => $data)
                {
                    //rozlozime radwk na data mezerami
                    $row_data = explode(' ', $data);
		    //aktivni radek obsahujici UPS zacina MONITOR
                    if("$row_data[0]"=="MONITOR"){
			//parsnut data
                        $ups[$this->app->data['count']]['name']=trim($row_data[2],'"');
                        $ups[$this->app->data['count']]['ip']=$row_data[1];
			//zjisteni aktualniho statusu
                        $ups[$this->app->data['count']]['status']=trim(exec("apcaccess -h $row_data[1] 2>/dev/null | grep \"STATUS\" | cut -d':' -f2"));
                        $ups[$this->app->data['count']]['timeleft']=trim(exec("apcaccess -h $row_data[1] 2>/dev/null | grep \"TIMELEFT\" | cut -d':' -f2"));

			/*
			    siulace vypadku
			
			if(	
				$row_data[1]=="192.168.133.220" //pxDomain
			    OR  $row_data[1]=="192.168.132.241" //WebyProxy
			    OR  $row_data[1]=="192.168.133.181" //pxWebk
			    OR  $row_data[1]=="192.168.132.202" //MySQL
			    OR  $row_data[1]=="192.168.132.221" //Katalogy
			    OR  $row_data[1]=="192.168.133.214" //pxMail

			    ){
			    
                            $ups[$this->app->data['count']]['status']="ONBATT";

			}
			
			
			    siulace vypadku
			*/

			//aktualizace pocitadel dle statusu
                        if($ups[$this->app->data['count']]['status']=="ONBATT"){
                            $this->app->data['onBatt']++;
                        }
                        if($ups[$this->app->data['count']]['status']=="ONLINE"){
                            $this->app->data['online']++;
                        }
			//pokud neni UPS v DB (dle IP) hodime do db stav kazde UPS
			if( ! $this->existUPSinDB( $row_data[1] ) ){
			    $this->insertUPS(array(
						"ip" => $row_data[1],
						"status"=> $ups[$this->app->data['count']]['status']
						));
			}
                        $this->app->data['count']++;
			//$this->app->data['count']=0;
                    }
                }
		//pokud neni hodime do db i stav vseh UPS
		if( ! $this->existUPSinDB( "0.0.0.0" ) ){
		    $this->insertUPS(	array(
					"ip" => "0.0.0.0",
					"status"=> "ONLINE"
					));
		}
//		print_r($ups);
		return $ups;
        }
	public function existUPSinDB($ip)
	{
	    $query = $this->db->get_where('status', array('ip' => $ip) );
            return $query->num_rows();
	}
	public function statusUPSinDB($ip)
	{
	    $query = $this->db->get_where('status', array('ip' => $ip) );
	    $result=$query->result();
            return $result[0]->status;
	}
	public function insertUPS($udata)
	{
		$this->db->insert('status', 
			array(
				'ip' => $udata['ip'],
				'status' => $udata['status'],
				'lastChange' => date( DATE_ISO8601, time() )
			)
		);
	}

    public function lastChange($row)
    {
	$origin = strtotime( $row->lastChange );
	$now = strtotime( 'now' );
	return round( abs($now - $origin) );
    }

    public function sendEmail( $udata, $group='oneOnBatt', $upsky )
    {
	$this->email->clear();

        $qemily = $this->db->get_where( 'notify', array( $group => 1 ) );
        $poslatNa="";
        foreach ($qemily->result_array() as $row)
        {
            $poslatNa=($poslatNa!=""?$poslatNa.",":"").$row['email'];
        }
//	print_r($udata);
//	print_r($poslatNa);
        $this->email->from('it@kmhk.cz', 'Dohled na UPS');
        $this->email->to( $poslatNa );
	if(isset($udata['name'])){
	    if($udata['status']=="ONLINE"){
        	$this->email->subject("UPS-".$udata['name']." je OK");
	    }elseif($udata['status']=="ONBATT"){
		$msg="Vypadek UPS-".$udata['name']." do vypnuti serveru cca : " . $udata['timeleft'];
        	$this->email->subject($msg);
		$this->email->message($msg);
	    }else{
        	$msg="UPS-".$udata['name']." ".$udata['status'];
        	$this->email->subject($msg);
		$this->email->message($msg);
	    }
	}else{
	    if($udata['status']=="ONLINE"){
        	$msg="El. energie v CCV je OK";
        	$this->email->subject($msg);
		$this->email->message($msg);
	    }elseif($udata['status']=="ONBATT"){
        	$msg="Vypadek el. energie v CCV!";
		//print_r($upsky);
		$upsinfo="";
		foreach($upsky as $upsid=>$ups){
		    $upsinfo.=$ups['name']." = ".$ups['timeleft']."
";
		}
        	$this->email->subject($msg);
		$this->email->message($upsinfo);
	    }
	}

//	$this->email->message('Testing the email class.');
	$this->email->send();
    }

    public function updateUPS($udata)
    {
        // aktualizujeme status UPS v DB
        $this->db->where('ip', $udata['ip']);
        $this->db->update('status',
                        array(
                            'status' => $udata['status'],
                            'lastChange' => date( DATE_ISO8601, time() )
                            )
                        );
    }
}