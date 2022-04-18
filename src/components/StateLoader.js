import { Card, Spin } from "antd"

const StateLoader = (props) => {
  return (
    <Spin spinning={props.loading} tip='Loading...'>
        <Card style={{width: '99%', marginTop: '25px', height: '450px'}} ></Card>
    </Spin>
  )
}

export default StateLoader