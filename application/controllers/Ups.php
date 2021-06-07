<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ups extends CI_Controller {

	public function check()
	{
		if(!is_cli()){
		    die("NOPE");
		}
		$this->load->model('Ups_model');

		$this->data['count']=0;
		$this->data['onBatt']=0;
		$this->data['online']=0;

		// projde config a prikazem zjisti statusy UPS z configu
		$ups=$this->Ups_model->readHostConf();
//		print_r($ups);
//		die();

		// zkoumame jestli nastala zmena u jednotlivych UPS
		foreach($ups as $uid => $udata)
		{
		    // hledame UPS v DB
		    $query = $this->db->get_where('status', array('ip' => $udata['ip']) );
		    if( $query->num_rows()==0 ){
			// pokud tam IP neni prida se
			$this->Ups_model->insertUPS($udata);
		    }else{
			$row = $query->row();
			if($udata['status']!=$row->status)
			{
			    //pokud je jiny status nez byl zjistime pred jakou dobou
			    // zmena musi byt stara vice nez 90 sec
			    if( $this->Ups_model->lastChange($row) > 90 )
			    {
				// jednotlive UPS posilat jen pokud nejsou ONBATT vsechny
				if($this->data['count']!=$this->data['onBatt'])
				{
				    $this->Ups_model->sendEmail($udata, 'oneOnBatt');
				    // TODO LOG?
				}
				// aktualizujeme status UPS v DB
				$this->Ups_model->updateUPS($udata);
				
			    }
			}
		    }
		}
		// vypadlo vsechno?
		$query = $this->db->get_where( 'status' , array('ip' => '0.0.0.0') );
		    if( $query->num_rows()==0 )
		    {
			$this->Ups_model->insertUPS(array(
						    'ip' => '0.0.0.0',
						    'status' => ( $this->data['count']!=$this->data['onBatt']?"ONLINE":"ONBATT" ),
						));
		    }else{
			$row = $query->row();
			if($udata['status']!=$row->status){
			    // zmena musi byt stara vice nez 30 sec
			    if( $this->Ups_model->lastChange($row) > 30 ){
				$this->Ups_model->sendEmail($udata, 'allOnBatt');
				//echo ( $this->data['count'] != $this->data['onBatt'] ? "EL OK" : "Vypadek EL" );
				$this->Ups_model->updateUPS(array(
						    'ip' => '0.0.0.0',
						    'status' => ( $this->data['count']!=$this->data['onBatt']?"ONLINE":"ONBATT" ),
						));
			    }
			}
		    }
	}
}
