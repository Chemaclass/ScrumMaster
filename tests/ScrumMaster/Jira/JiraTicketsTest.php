<?php

declare(strict_types=1);

namespace App\Tests\ScrumMaster;

use App\ScrumMaster\Jira\JiraTickets;
use App\ScrumMaster\Jira\ReadModel\Assignee;
use App\ScrumMaster\Jira\ReadModel\JiraTicket;
use PHPUnit\Framework\TestCase;

final class JiraTicketsTest extends TestCase
{
    /** @test */
    public function fromJira(): void
    {
        $rawContent = '{"expand":"schema,names","startAt":0,"maxResults":50,"total":2,"issues":[{"expand":"operations,versionedRepresentations,editmeta,changelog,renderedFields","id":"69120","self":"https://company-name.atlassian.net/rest/api/3/issue/69120","key":"CST-244","fields":{"statuscategorychangedate":"2019-10-22T17:07:52.459+0200","issuetype":{"self":"https://company-name.atlassian.net/rest/api/3/issuetype/10002","id":"10002","description":"A task that needs to be done.","iconUrl":"https://company-name.atlassian.net/secure/viewavatar?size=medium&avatarId=10318&avatarType=issuetype","name":"Task","subtask":false,"avatarId":10318},"timespent":null,"project":{"self":"https://company-name.atlassian.net/rest/api/3/project/11250","id":"11250","key":"CST","name":"Core Service Team ","projectTypeKey":"software","simplified":false,"avatarUrls":{"48x48":"https://company-name.atlassian.net/secure/projectavatar?pid=11250&avatarId=10201","24x24":"https://company-name.atlassian.net/secure/projectavatar?size=small&s=small&pid=11250&avatarId=10201","16x16":"https://company-name.atlassian.net/secure/projectavatar?size=xsmall&s=xsmall&pid=11250&avatarId=10201","32x32":"https://company-name.atlassian.net/secure/projectavatar?size=medium&s=medium&pid=11250&avatarId=10201"}},"fixVersions":[],"aggregatetimespent":null,"resolution":null,"customfield_10950":null,"customfield_10940":null,"customfield_10941":null,"customfield_10942":{"version":1,"type":"doc","content":[{"type":"paragraph","content":[]}]},"customfield_10700":null,"customfield_10943":null,"customfield_10944":null,"customfield_10900":null,"customfield_10945":null,"resolutiondate":null,"customfield_10948":null,"workratio":-1,"customfield_10949":null,"watches":{"self":"https://company-name.atlassian.net/rest/api/3/issue/CST-244/watchers","watchCount":3,"isWatching":false},"lastViewed":"2019-10-23T11:04:52.635+0200","created":"2019-10-18T15:02:20.269+0200","customfield_10100":null,"priority":{"self":"https://company-name.atlassian.net/rest/api/3/priority/2","iconUrl":"https://company-name.atlassian.net/images/icons/priorities/critical.svg","name":"High","id":"2"},"customfield_10980":null,"customfield_10101":null,"customfield_10982":null,"customfield_10300":"{}","labels":[],"customfield_10930":null,"customfield_10975":null,"customfield_10931":null,"customfield_10932":null,"customfield_10976":null,"aggregatetimeoriginalestimate":null,"timeestimate":null,"customfield_10977":null,"customfield_10933":null,"customfield_10978":null,"versions":[],"customfield_10934":null,"customfield_10979":null,"customfield_10935":null,"customfield_10936":null,"customfield_10937":null,"customfield_10938":null,"issuelinks":[{"id":"80124","self":"https://company-name.atlassian.net/rest/api/3/issueLink/80124","type":{"id":"10400","name":"Issue split","inward":"split from","outward":"split to","self":"https://company-name.atlassian.net/rest/api/3/issueLinkType/10400"},"outwardIssue":{"id":"69237","key":"CST-249","self":"https://company-name.atlassian.net/rest/api/3/issue/69237","fields":{"summary":"Don\'t Allow More Than X Shipments Per Order","status":{"self":"https://company-name.atlassian.net/rest/api/3/status/10000","description":"","iconUrl":"https://company-name.atlassian.net/","name":"Backlog","id":"10000","statusCategory":{"self":"https://company-name.atlassian.net/rest/api/3/statuscategory/2","id":2,"key":"new","colorName":"blue-gray","name":"To Do"}},"priority":{"self":"https://company-name.atlassian.net/rest/api/3/priority/3","iconUrl":"https://company-name.atlassian.net/images/icons/priorities/medium.svg","name":"Medium","id":"3"},"issuetype":{"self":"https://company-name.atlassian.net/rest/api/3/issuetype/10100","id":"10100","description":"An improvement or enhancement to an existing feature or task.","iconUrl":"https://company-name.atlassian.net/secure/viewavatar?size=medium&avatarId=10310&avatarType=issuetype","name":"Improvement","subtask":false,"avatarId":10310}}}}],"customfield_10939":null,"assignee":{"self":"https://company-name.atlassian.net/rest/api/3/user?accountId=5a72f8d3cb80172501b","name":"assignee-name","key":"assignee-key","accountId":"5a72f8d3cb80172501b","emailAddress":"person@companymail.com","avatarUrls":{"48x48":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/5a72f8d3cb80172501b/ec006b06-9936-4157-93d6-a95d6c4/128?size=48&s=48","24x24":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/5a72f8d3cb80172501b/ec006b06-9936-4157-93d6-a95d6646e4c4/128?size=24&s=24","16x16":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/5a72f8d3cb80172501b/ec006b06-9936-4157-93d6-a95d6646e4c4/128?size=16&s=16","32x32":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/5a72f8d3cb80172501b/ec006b06-9936-4157-93d6-a95d6646e4c4/128?size=32&s=32"},"displayName":"Name Surname","active":true,"timeZone":"Europe/Berlin","accountType":"atlassian"},"updated":"2019-10-23T09:58:46.500+0200","status":{"self":"https://company-name.atlassian.net/rest/api/3/status/10101","description":"","iconUrl":"https://company-name.atlassian.net/","name":"In Review","id":"10101","statusCategory":{"self":"https://company-name.atlassian.net/rest/api/3/statuscategory/4","id":4,"key":"indeterminate","colorName":"yellow","name":"In Progress"}},"components":[],"timeoriginalestimate":null,"description":{"version":1,"type":"doc","content":[{"type":"heading","attrs":{"level":1},"content":[{"type":"text","text":"Description"}]},{"type":"paragraph","content":[{"type":"text","text":"According to ShopApotheke, they transfer shipments in batches every day around 8:45pm. "},{"type":"hardBreak"},{"type":"text","text":"When checking Sendwise/ ANA db, we create shipments from 08:30pm same day until next day in the morning around 6am. "}]},{"type":"paragraph","content":[{"type":"text","text":"This would mean we process the shipments, they sent us in a batch the whole night. Since the customer is connected via API, this should not be the case. "}]},{"type":"paragraph","content":[{"type":"hardBreak"},{"type":"text","text":"Also keeping in mind that with the ASOS integration, we receive an additional batch of shipments with around 5000 shipments and more around 9:30pm every day. We need to ensure that the transactions are processed quickly. But ASOS does not use API. "}]},{"type":"paragraph","content":[]},{"type":"heading","attrs":{"level":1},"content":[{"type":"text","text":"AC"}]},{"type":"bulletList","content":[{"type":"listItem","content":[{"type":"paragraph","content":[{"type":"text","text":"Define root cause for slow response time: creating shipments until next day in the morning in ANA db"}]}]},{"type":"listItem","content":[{"type":"paragraph","content":[{"type":"text","text":"Define proposal how to fix it "}]}]},{"type":"listItem","content":[{"type":"paragraph","content":[{"type":"text","text":"Evaluate risk when ASOS parcels also need to be processed "}]}]}]},{"type":"paragraph","content":[]}]},"customfield_10012":"1|i01ozm:","customfield_10005":8.0,"customfield_10600":null,"security":null,"customfield_10007":["com.atlassian.greenhopper.service.sprint.Sprint@2b843eb[id=540,rapidViewId=144,state=ACTIVE,name=CW 42-43,goal=1. Integrate ASOS Post Nord SE \n2. Claimable Policy \n3. Support Parseur Integration Asket,startDate=2019-10-14T09:50:01.953Z,endDate=2019-10-25T14:49:00.000Z,completeDate=,sequence=519]"],"customfield_10800":null,"customfield_10009":null,"aggregatetimeestimate":null,"customfield_10925":[],"customfield_10926":null,"customfield_10927":null,"customfield_10928":null,"customfield_10929":null,"summary":"Ticket title :)","creator":{"self":"https://company-name.atlassian.net/rest/api/3/user?accountId=557058%3A7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d","name":"g.fischer","key":"g.fischer","accountId":"557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d","emailAddress":"g.fischer@company-name.com","avatarUrls":{"48x48":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d/2ab3cf1c-45e1-4258-a406-e14cb7e789d8/128?size=48&s=48","24x24":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d/2ab3cf1c-45e1-4258-a406-e14cb7e789d8/128?size=24&s=24","16x16":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d/2ab3cf1c-45e1-4258-a406-e14cb7e789d8/128?size=16&s=16","32x32":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d/2ab3cf1c-45e1-4258-a406-e14cb7e789d8/128?size=32&s=32"},"displayName":"Giulia Fischer","active":true,"timeZone":"Europe/Berlin","accountType":"atlassian"},"subtasks":[],"reporter":{"self":"https://company-name.atlassian.net/rest/api/3/user?accountId=557058%3A7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d","name":"g.fischer","key":"g.fischer","accountId":"557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d","emailAddress":"g.fischer@company-name.com","avatarUrls":{"48x48":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d/2ab3cf1c-45e1-4258-a406-e14cb7e789d8/128?size=48&s=48","24x24":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d/2ab3cf1c-45e1-4258-a406-e14cb7e789d8/128?size=24&s=24","16x16":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d/2ab3cf1c-45e1-4258-a406-e14cb7e789d8/128?size=16&s=16","32x32":"https://avatar-management--avatars.us-west-2.prod.public.atl-paas.net/557058:7ab92837-48d7-4fdd-9e4c-ae2bc7b5d19d/2ab3cf1c-45e1-4258-a406-e14cb7e789d8/128?size=32&s=32"},"displayName":"Giulia Fischer","active":true,"timeZone":"Europe/Berlin","accountType":"atlassian"},"aggregateprogress":{"progress":0,"total":0},"customfield_10000":"2019-10-22T17:10:58.039+0200","customfield_10001":null,"customfield_10200":{"hasEpicLinkFieldDependency":false,"showField":false,"nonEditableReason":{"reason":"PLUGIN_LICENSE_ERROR","message":"Portfolio for Jira must be licensed for the Parent Link to be available."}},"customfield_10960":null,"customfield_10003":null,"customfield_10004":null,"customfield_10400":null,"customfield_10951":null,"environment":null,"customfield_10954":null,"customfield_10955":null,"customfield_10956":null,"duedate":null,"progress":{"progress":0,"total":0},"votes":{"self":"https://company-name.atlassian.net/rest/api/3/issue/CST-244/votes","votes":0,"hasVoted":false}}}]}';

        $this->assertEquals([
            new JiraTicket(
                $title = 'Ticket title :)',
                $key = 'CST-244',
                $status = 'In Review',
                new Assignee(
                    $name = 'assignee-name',
                    $key = 'assignee-key',
                    $emailAddress = 'person@companymail.com',
                    $displayName = 'Name Surname'
                ),
                $storyPoints = 8
            ),
        ], JiraTickets::fromJira(\json_decode($rawContent, true)));
    }
}
