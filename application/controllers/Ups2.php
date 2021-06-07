<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ups2 extends CI_Controller {

	public function check()
	{
		// musi byt spusteno z prikazoveho radku
		if(!is_cli()){
		    die("NOPE");
		}
		$this->load->model('Ups_model');

		//celkovy pocetups
		$this->data['count']=0;
		//pocetups ktere jsou bez EL
		$this->data['onBatt']=0;
		//pocet UPS OK
		$this->data['online']=0;

		// projde config, prikazem zjisti statusy UPS, zkontroluje jestli jsou v DB
		$ups=$this->Ups_model->readHostConf();
		if($this->data['count']==0){
		    die("Nenalezena zadna UPS! V host.conf");
		}

		//zadna UPS neni ONBATT
		if($this->data['onBatt']<=0)
		{
		    if($this->data['onBatt']==0)
		    {
		    //po vypadku? status 0.0.0.0 v DB?
		    if($this->Ups_model->statusUPSinDB("0.0.0.0")!="ONLINE"){
			//po celkovem vypadku poslat OK email/SMS
			$this->Ups_model->sendEmail( array( 'ip' => '0.0.0.0', 'status' => "ONLINE" ), 'allOnBatt', $ups );
			//aktualizovat status vseho! v DB dle aktualniho stavu
			$this->Ups_model->updateUPS(array( 'ip' => '0.0.0.0', 'status' => "ONLINE" ));
			//zaroven update statusu vsech jednotlivych UPS aby to nehlasilo jejich naskoceni
			foreach($ups as $u=>$val){
				if($this->Ups_model->statusUPSinDB($val['ip'])!="ONLINE"){
					$this->Ups_model->updateUPS(array( 'ip' => $val['ip'], 'status' => "ONLINE" ));
				}
			}
		    }else{
			//pokud nebezi vsechny UPS zkoumame jednotlive
			    foreach($ups as $u=>$val){
				//posilame ty co jsou nove online
				
				if($this->Ups_model->statusUPSinDB($val['ip'])!="ONLINE"){
				    if( strtotime($val['lastChange']) > strtotime("-1 minute") ){
					//poslat info o nabehu jedne UPS
					$this->Ups_model->sendEmail( $val , 'oneOnBatt', $ups);
				    }
				    //aktualizovat status v DB
				    $this->Ups_model->updateUPS( $val );
				}
			    }
		    }
		    }else{
			//nemuze byt mene nez 0 ONBAT
		        die("NESTANDARTNI STAV! ONBATT <0");
		    }
		
		//vsehny onbatt - vypadek proudu!!! zasilat info kazdych 5 min
		//TODO idealne jeste zjistit a posilat zbyvajici cas ONBAT nez lehne prvni vec
		}elseif($this->data['onBatt']==$this->data['count']){
		    $allUPS=array( 'ip' => '0.0.0.0', 'status' => "ONBATT" );
//		    if( $this->Ups_model->lastChange( $allUPS ) < 300 ){
			//vsechny UPS ve stavu ONBATT - vypadek proudu v celem CCV
			$this->Ups_model->sendEmail( $allUPS, 'allOnBatt', $ups );
			//aktualizovat status vseho! v DB dle aktualniho stavu
			$this->Ups_model->updateUPS( $allUPS );
//		    }
		}else{
			foreach($ups as $u=>$val){
			//posilame ty co jsou nove onbatt
			    if($this->Ups_model->statusUPSinDB($val['ip'])!="ONBATT" AND $val['status']=="ONBATT"){
//				if( $this->Ups_model->lastChange( $val ) < 300 ){
				    //aktualizovat status v DB
				    $this->Ups_model->updateUPS( $val );
				    if( strtotime($val['lastChange']) > strtotime("-1 minute") ){
					//poslat info vypadku jedne UPS (pokud je off dele nez jednu minutu)
					$this->Ups_model->sendEmail( $val , 'oneOnBatt', $ups);
				    }
//				}
			    }
			}
		}
	}


}
