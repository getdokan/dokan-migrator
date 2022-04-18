
import "antd/dist/antd.css";
import './App.css';
import { Alert, Button, Card, Col, Row, notification, } from 'antd';

import DokanMigrator from './DokanMigrator'

import { useState, useEffect, } from 'react'
import axios from 'axios'
import { CheckCircleFilled, SmileOutlined, WarningFilled } from '@ant-design/icons';
import StateLoader from './StateLoader'


function App() {
    const [ type, setType ] = useState('vendor');

    const [ vendorStater, setVendorStarter ] = useState( false );
    const [ orderStater, setOrderStarter ] = useState( false );
    const [ withdrawStater, setWithdrawStarter ] = useState( false );
    const [ loading, setLoading ] = useState(false);
    const [ completed, setCompleted ] = useState(false);
    const [ enableVendorDashboard, setEnableVendorDashboard ] = useState(false);
    const [ lastCompleted, setLastCompleted ] = useState({
      vendor: false,
      order: false,
      withdraw: false
    });
    const [ migratable, setMigratable ] = useState(true);
    const [ stateLoading, setStateLoading ] = useState(true);
    const [ migrationSuccess, setMigrationSuccess ] = useState(false);
    const [ title, setTitle ] = useState('Migrate to Dokan');

    useEffect(()=>{
      setStateLoading(true);
      setLoading(true);

      axios.post( dokan_migrator.ajax_url, null, { params: {
        action: 'dokan_migrator_last_migrated',
        nonce: dokan_migrator.nonce,
      } } )
      .then(res=>{
        setType( res.data.data.last_migrated != 'undefined' ? res.data.data.last_migrated : 'vendor' );
        setMigratable( res.data.data.migratable != 'undefined' ? res.data.data.migratable : false );
        setMigrationSuccess( res.data.data.migration_success != 'undefined' ? res.data.data.migration_success : false );
        setTitle( res.data.data.set_title != 'undefined' ? res.data.data.set_title : 'Migrate to Dokan' );

        let oldData = {...lastCompleted};
        switch (res.data.data) {
          case 'order':
            oldData.vendor = true;
            break;

          case 'withdraw':
            oldData.vendor = true;
            oldData.order = true;
            break;
        }

        setLastCompleted(oldData);
        setStateLoading(false);
      });
    },[]);

    function updateMigrationState( migrated ) {
      switch (migrated) {
        case 'vendor':
          setOrderStarter(true);
          break;

        case 'order':
          setWithdrawStarter(true);
          break;

        case 'withdraw':
          setCompleted(true);
          setEnableVendorDashboard(true);
          openNotification();
          break;
      }

    }

    function startMigration( start = type ) {
      switch (start) {
        case 'vendor':
          setVendorStarter(true);
          break;

        case 'order':
          setOrderStarter(true);
          break;

        case 'withdraw':
          setWithdrawStarter(true);
          break;
      }
    }

    function activeVendorDashboard() {
      axios.post( dokan_migrator.ajax_url, null, { params: {
        action: 'dokan_migrator_active_vendor_dashboard',
        nonce: dokan_migrator.nonce,
      } } )
      .then(res=>{
        if (res.data.success) {
          setEnableVendorDashboard(false);
        }
      });
    }

    const openNotification = () => {
      notification.open({
        message: 'Congratulations.',
        description:
          'You have successful migrated to Dokan. Enjoy 🎉',
        icon: <SmileOutlined style={{ color: '#52C519' }} />,
        placement: 'bottomRight'
      });
    };

    const migrationCard = () => {
      return(
        <Card
            style={{width: '99%', marginTop: '25px'}}
            title={title}
          >
            <Row  gutter={[16, 16]}>
              <DokanMigrator
                title="Vendor"
                type="vendor"
                url={dokan_migrator.ajax_url}
                nonce={dokan_migrator.nonce}
                number={10}
                updateLoading={(data)=>setLoading(data)}
                startAutoMigration={vendorStater}
                updateMigrationState={updateMigrationState}
                lastCompleted={lastCompleted.vendor}
                migrate={migratable}
              />
              <DokanMigrator
                title="Order"
                type="order"
                url={dokan_migrator.ajax_url}
                nonce={dokan_migrator.nonce}
                number={5}
                updateLoading={(data)=>setLoading(data)}
                startAutoMigration={orderStater}
                updateMigrationState={updateMigrationState}
                lastCompleted={lastCompleted.order}
                migrate={migratable}
              />
              <DokanMigrator
                title="Withdraw"
                type="withdraw"
                url={dokan_migrator.ajax_url}
                nonce={dokan_migrator.nonce}
                number={10}
                updateLoading={(data)=>setLoading(data)}
                startAutoMigration={withdrawStater}
                updateMigrationState={updateMigrationState}
                lastCompleted={lastCompleted.withdraw}
                migrate={migratable}
              />
            </Row>
            <Row  gutter={[16, 16]} style={{marginTop: '20px'}}>
              <Col span={24}>
                { enableVendorDashboard ?  <Alert
                  style={{ width: '100%' }}
                  message="Active dokan vendor dashboard."
                  type="success"
                  showIcon
                  action={
                    <Button onClick={activeVendorDashboard} className="dokan-migration-active-v-dash-btn" size="middle" type="primary">
                      Active
                    </Button>
                  }
                />
                :''}
                { ! completed ?
                  <Button onClick={()=>startMigration(type)} type="primary" loading={loading}>Start migration</Button>
                  : ''
                }
              </Col>
            </Row>
        </Card>
      );
    }

    const successOrWarningUi = (message, success=false) => {
      let color = success ? '#95de64' : '#ff7875';
      return(
        <div
          style={{
            width: '99%',
            height: '450px',
            background: '#FFF',
            display: 'flex',
            alignContent: 'center',
            justifyContent: 'center',
            alignItems: 'center',
            flexDirection: 'column',
            marginTopL: '25px'
          }}
        >
          {
            success ?
              <CheckCircleFilled style={{fontSize:'70px', marginBottom:'30px', color:color}} />
            :
              <WarningFilled style={{fontSize:'70px', marginBottom:'30px', color:color}} />
          }
          <h2 style={{color:color}}>{message}</h2>
        </div>
      );
    }

    const successUiOrMigrationUi = () => {
      return(
        migrationSuccess ?
          successOrWarningUi('You have successfully migrated to dokan.',true)
        :
          migrationCard()
      );
    }

    const migrationUi = () => {
      return(
        migratable ?
          successUiOrMigrationUi()
        :
          migrationSuccess ?
            successOrWarningUi('You have successfully migrated to dokan.',true)
          :
            successOrWarningUi('No plugin found to migrate to dokan')
      );
    }

  return (
    <>
    {
      stateLoading ?
        <StateLoader loading={stateLoading}/>
      :
      migrationUi()
    }
  </>
  )
}

export default App;