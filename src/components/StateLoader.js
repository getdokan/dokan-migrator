import { __ } from "@wordpress/i18n/build-types"
import { Card, Spin } from "antd"

const StateLoader = (props) => {
  return (
    <Spin spinning={props.loading} tip={ __( 'Loading...', 'dokan-migrator' ) }>
        <Card style={{width: '99%', marginTop: '25px', height: '450px'}} ></Card>
    </Spin>
  )
}

export default StateLoader