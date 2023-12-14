import { useEffect, useState } from 'react';
import { Card, Col, Nav, Row, Tab } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { formatTime } from '../components/format-time';
import diacritics from 'diacritics';
import { pathName } from '@/routes/path-name';
import { Skeleton } from '@mui/material';

type MyQaProps = {
  listQa: any;
  isLoading: boolean;
  isFetching: boolean;
  hasNextPage: any;
  fetchNextPage: any;
  endRef: any;
  endInView: any;
};

export const MyListQa = ({
  listQa,
  isLoading,
  isFetching,
  hasNextPage,
  fetchNextPage,
  endInView,
  endRef,
}: MyQaProps) => {
  const [searchQueries, setSearchQueries] = useState<any>({
    about1: '',
    about2: '',
  });

  const handleSearchInputChange = (tabKey: any, query: string) => {
    setSearchQueries({ ...searchQueries, [tabKey]: query });
  };

  const clearSearch = (tabKey: any) => {
    setSearchQueries({ ...searchQueries, [tabKey]: '' });
  };

  const normalizeText = (text: string) => diacritics.remove(text.toLowerCase());

  const filterList = (list: any, query: string) =>
    list && list.filter((item: any) => normalizeText(item.title).includes(normalizeText(query)));

  const renderTabPaneContent = (tabKey: string, filteredList: any) => {
    return (
      <>
        <div className="d-flex justify-content-between">
          <h4>{tabKey === 'about1' ? 'Danh sách câu hỏi' : 'Câu hỏi đã trả lời'}</h4>
          <div className="d-flex align-items-center">
            <div className="form-outline">
              <input
                type="search"
                id={`form-${tabKey}`}
                style={{ height: '35px' }}
                className="form-control"
                value={searchQueries[tabKey]}
                placeholder="Tìm kiếm..."
                onChange={e => handleSearchInputChange(tabKey, e.target.value)}
              />
            </div>
          </div>
        </div>
        <hr />
        {isLoading ? (
          <>
            <Row className="mb-2">
              <div className="col-12">
                <Skeleton variant="text" width="100%" height={100} />
              </div>
              <hr />
            </Row>
            <Row className="mb-2">
              <div className="col-12">
                <Skeleton variant="text" width="100%" height={100} />
              </div>
            </Row>
          </>
        ) : (
          <div className={`${isSticky ? 'scroller-my-blog' : ''}`}>
            {filteredList && filteredList.length > 0 ? (
              <>
                {filteredList.map((item: any, index: number) => (
                  <Row className="mb-2" key={index}>
                    <div className="col-12">
                      <Link
                        className="text-dark font-bold"
                        style={{ fontSize: '20px' }}
                        to={`${pathName.QUESTS_DETAIL}/${item.id}`}
                      >
                        {item.title}
                      </Link>
                    </div>
                    <div className="col-12">
                      <p className="mb-0">Cập nhật lần cuối: {formatTime(item.updated_at)}</p>
                    </div>
                    <hr />
                  </Row>
                ))}
                <div ref={endRef}></div>
              </>
            ) : (
              <h4>Không có data</h4>
            )}
          </div>
        )}
      </>
    );
  };

  const filteredListAbout1 = filterList(listQa, searchQueries.about1);
  const filteredListAbout2 = filterList(listQa, searchQueries.about2);

  const [isSticky, setSticky] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      const offset = window.scrollY;
      const topThreshold = 300;

      if (offset >= topThreshold) {
        setSticky(true);
      } else {
        setSticky(false);
      }
    };

    window.addEventListener('scroll', handleScroll);

    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, []);

  return (
    <>
      <Tab.Container id="left-tabs-example" defaultActiveKey="about1">
        <Row>
          <Col md={3}>
            <Card>
              <Card.Body>
                <Nav variant="pills" className=" basic-info-items list-inline d-block p-0 m-0">
                  <Nav.Item>
                    <Nav.Link href="#qa" eventKey="about1" className="d-flex align-items-center gap-3">
                      <i className="material-symbols-outlined">create</i> Câu hỏi của bạn
                    </Nav.Link>
                  </Nav.Item>
                </Nav>
                <Nav variant="pills" className=" basic-info-items list-inline d-block p-0 m-0">
                  <Nav.Item>
                    <Nav.Link href="#commentedQuestions" eventKey="about2" className="d-flex align-items-center gap-3">
                      <i className="material-symbols-outlined">comment</i> Câu hỏi đã trả lời
                    </Nav.Link>
                  </Nav.Item>
                </Nav>
              </Card.Body>
            </Card>
          </Col>
          <Col md={9} className="ps-4">
            <Card>
              <Card.Body>
                <Tab.Content>
                  <Tab.Pane eventKey="about1">{renderTabPaneContent('about1', filteredListAbout1)}</Tab.Pane>
                  <Tab.Pane eventKey="about2">{renderTabPaneContent('about2', filteredListAbout2)}</Tab.Pane>
                </Tab.Content>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      </Tab.Container>
    </>
  );
};
