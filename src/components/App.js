
import "antd/dist/antd.css";
import './App.css';
import { Alert, Button, Card, Col, Row, notification, Modal } from 'antd';
import { __ } from '@wordpress/i18n'

import DokanMigrator from './DokanMigrator'

import { useState, useEffect, } from 'react'
import { CheckCircleFilled, SmileOutlined, WarningFilled, ExclamationCircleOutlined } from '@ant-design/icons';
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
    const [ resetLoading, setResetLoading ] = useState(false);

    useEffect(()=>{
      setStateLoading(true);
      setLoading(true);

      jQuery.post( dokan_migrator.ajax_url,
        {
          action: 'dokan_migrator_last_migrated',
          nonce: dokan_migrator.nonce,
        } )
      .done( function (res) {
        setType( res.data.last_migrated != 'undefined' ? res.data.last_migrated : 'vendor' );
        setMigratable( res.data.migratable != 'undefined' ? res.data.migratable : false );
        setMigrationSuccess( res.data.migration_success != 'undefined' ? res.data.migration_success : false );
        setTitle( res.data.set_title != 'undefined' ? res.data.set_title : 'Migrate to Dokan' );

        let oldData = {...lastCompleted};
        switch (res.data) {
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
      jQuery.post( dokan_migrator.ajax_url,
        {
          action: 'dokan_migrator_active_vendor_dashboard',
          nonce: dokan_migrator.nonce,
        } )
      .done( function (res) {
        if (res.success) {
          setEnableVendorDashboard(false);
        }
      });
    }

    const openNotification = () => {
      notification.open({
        message: __( 'Congratulations.', 'dokan-migrator' ),
        description: __( 'You have successful migrated to Dokan. Enjoy 🎉', 'dokan-migrator' ),
        icon: <SmileOutlined style={{ color: '#52C519' }} />,
        placement: 'bottomRight'
      });
    };

    function handleResetAndRestart() {
      Modal.confirm({
        title: __( 'Re-run migration?', 'dokan-migrator' ),
        icon: <ExclamationCircleOutlined />,
        content: __( 'This will reset previous migration progress and re-enable the migration steps. Do you want to continue?', 'dokan-migrator' ),
        okText: __( 'Yes, re-run', 'dokan-migrator' ),
        cancelText: __( 'Cancel', 'dokan-migrator' ),
        onOk: () => {
          setResetLoading(true);
          setStateLoading(true);
          return jQuery.post( dokan_migrator.ajax_url, {
            action: 'reset_and_restart_migration',
            nonce: dokan_migrator.nonce,
          }).done((res) => {
            if (res && res.success) {
              setMigrationSuccess(false);
              setCompleted(false);
              setEnableVendorDashboard(false);
              setLastCompleted({ vendor: false, order: false, withdraw: false });
              setVendorStarter(false);
              setOrderStarter(false);
              setWithdrawStarter(false);
              setType('vendor');
              setTitle( __( 'Migrate to Dokan', 'dokan-migrator' ) );

              notification.success({
                message: __( 'Reset complete', 'dokan-migrator' ),
                description: __( 'You can now re-run the migration.', 'dokan-migrator' ),
                placement: 'bottomRight',
              });
            } else {
              notification.error({
                message: __( 'Reset failed', 'dokan-migrator' ),
                description: (res && res.data && res.data.message) ? res.data.message : __( 'Unexpected error occurred.', 'dokan-migrator' ),
                placement: 'bottomRight',
              });
            }
          }).fail(() => {
            notification.error({
              message: __( 'Network error', 'dokan-migrator' ),
              description: __( 'Could not contact the server. Please try again.', 'dokan-migrator' ),
              placement: 'bottomRight',
            });
          }).always(() => {
            setResetLoading(false);
            setStateLoading(false);
          });
        },
      });
    }

    const migrationCard = () => {
      return(
        <Card
            style={{width: '99%', marginTop: '25px'}}
            title={title}
          >
            <Row  gutter={[16, 16]}>
              <DokanMigrator
                title={__( 'Vendor', 'dokan-migrator' )}
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
                title={__( 'Order', 'dokan-migrator' )}
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
                title={__( 'Withdraw', 'dokan-migrator' )}
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
                  message={__( 'Active dokan vendor dashboard.', 'dokan-migrator' )}
                  type="success"
                  showIcon
                  action={
                    <Button onClick={activeVendorDashboard} className="dokan-migration-active-v-dash-btn" size="middle" type="primary">
                      {__( 'Active', 'dokan-migrator' )}
                    </Button>
                  }
                />
                :''}
                { ! completed ?
                  <Button onClick={()=>startMigration(type)} type="primary" loading={loading}>{ __( 'Start migration', 'dokan-migrator' ) }</Button>
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

          { success && (
            <div style={{ marginTop: '20px' }}>
              <div style={{ marginBottom: '10px', textAlign: 'center' }}>
                { __( 'Do you want to re-run the migration?', 'dokan-migrator' ) }
              </div>
              <Button type="primary" onClick={handleResetAndRestart} loading={resetLoading}>
                { __( 'Re-run migration', 'dokan-migrator' ) }
              </Button>
            </div>
          ) }
        </div>
      );
    }

    const successUiOrMigrationUi = () => {
      return(
        migrationSuccess ?
          successOrWarningUi( __( 'You have successfully migrated to dokan.', 'dokan-migrator' ),true )
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
            successOrWarningUi( __( 'You have successfully migrated to dokan.', 'dokan-migrator' ), true )
          :
            successOrWarningUi( __( 'No plugin found to migrate to dokan', 'dokan-migrator' ) )
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