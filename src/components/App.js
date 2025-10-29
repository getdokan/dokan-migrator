
import "antd/dist/antd.css";
import './App.css';
import { Alert, Button, Card, Col, Row, notification, Modal, Result, Checkbox, Tooltip } from 'antd';
import { __ } from '@wordpress/i18n'

import DokanMigrator from './DokanMigrator'

import { useState, useEffect, } from 'react'
import { CheckCircleFilled, SmileOutlined, WarningFilled, ExclamationCircleOutlined, ReloadOutlined } from '@ant-design/icons';
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
    const [ selectedSteps, setSelectedSteps ] = useState({ vendor: true, order: true, withdraw: true });

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
        if (res.data && res.data.selected_steps) {
          setSelectedSteps(res.data.selected_steps);
        }

        let oldData = {...lastCompleted};
        switch (res.data.last_migrated) {
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

        // Auto-resume if there is an in-progress step
        if (res.data && res.data.in_progress) {
          startMigration(res.data.in_progress);
        }
      });
    },[]);

    // Determine the next selected step in the canonical order
    function getNextSelectedStep(current){
      const order = ['vendor','order','withdraw'];
      const idx = order.indexOf(current);
      for (let i = idx + 1; i < order.length; i++){
        if (selectedSteps[order[i]]) return order[i];
      }
      return '';
    }

    function updateMigrationState( migrated ) {
      const next = getNextSelectedStep(migrated);
      if (next === 'order') {
        setOrderStarter(true);
        return;
      }
      if (next === 'withdraw') {
        setWithdrawStarter(true);
        return;
      }

      // No next step selected: finish
      setCompleted(true);
      setEnableVendorDashboard(true);
      openNotification();
    }

    function getFirstSelectedStep(){
      if (selectedSteps.vendor) return 'vendor';
      if (selectedSteps.order) return 'order';
      if (selectedSteps.withdraw) return 'withdraw';
      return '';
    }

    function startMigration( start = type ) {
      let toStart = start;
      // If requested step is not selected, fallback to the first selected
      if (!selectedSteps[start]) {
        toStart = getFirstSelectedStep();
      }
      switch (toStart) {
        case 'vendor':
          setVendorStarter(true);
          break;

        case 'order':
          setOrderStarter(true);
          break;

        case 'withdraw':
          setWithdrawStarter(true);
          break;
        default:
          // nothing selected
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

    function saveSelectedSteps(next){
      setSelectedSteps(next);
      jQuery.post(dokan_migrator.ajax_url, {
        action: 'dokan_migrator_set_selected_steps',
        nonce: dokan_migrator.nonce,
        steps: JSON.stringify(next)
      }).done((res)=>{
        if(res && res.success){
          // ok
        }
      });
    }

    const migrationCard = () => {
      const options = [
        { label: __('Vendor','dokan-migrator'), value: 'vendor' },
        { label: __('Order','dokan-migrator'), value: 'order' },
        { label: __('Withdraw','dokan-migrator'), value: 'withdraw' },
      ];
      const checked = Object.keys(selectedSteps).filter(k=>selectedSteps[k]);
      const noneSelected = checked.length === 0;

      return(
        <Card
            style={{width: '99%', marginTop: '25px'}}
            title={title}
          >
            <Row gutter={[16,16]} style={{ marginBottom: '8px' }}>
              <Col span={24}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
                  <span style={{ fontWeight: 500 }}>{__('Choose what to migrate:','dokan-migrator')}</span>
                  <Checkbox.Group
                    options={options}
                    value={checked}
                    onChange={(list)=>{
                      const next = { vendor:false, order:false, withdraw:false };
                      list.forEach(v=> next[v] = true);
                      saveSelectedSteps(next);
                    }}
                  />
                  { noneSelected && (
                    <Tooltip title={__('Select at least one to start migration','dokan-migrator')}>
                      <span style={{ color:'#faad14' }}>{__('No steps selected','dokan-migrator')}</span>
                    </Tooltip>
                  )}
                </div>
              </Col>
            </Row>
            <Row  gutter={[16, 16]}>
              { selectedSteps.vendor && (
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
              )}
              { selectedSteps.order && (
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
              )}
              { selectedSteps.withdraw && (
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
              )}
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
                  <Button onClick={()=>startMigration(getFirstSelectedStep())} type="primary" disabled={noneSelected} loading={loading}>{ __( 'Start migration', 'dokan-migrator' ) }</Button>
                  : ''
                }
              </Col>
            </Row>
        </Card>
      );
    }

    const successOrWarningUi = (message, success=false) => {
      const status = success ? 'success' : 'warning';
      const subTitle = success
        ? __('Everything looks great. If you need, you can re-run the migration.', 'dokan-migrator')
        : __('We could not detect a compatible plugin to migrate from.', 'dokan-migrator');

      return (
        <Card style={{ width: '99%', marginTop: '25px' }} bodyStyle={{ padding: '48px 32px' }} className="dm-result-card">
          <Result
            status={status}
            title={message}
            subTitle={subTitle}
            extra={success ? [
              <Button key="rerun" type="link" size="small" icon={<ReloadOutlined />} onClick={handleResetAndRestart} loading={resetLoading} className="dm-rerun-link">
                {__('Re-run migration', 'dokan-migrator')}
              </Button>
            ] : null}
          />
        </Card>
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