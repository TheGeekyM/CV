<?php namespace Geeky\CV;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Class Parser
 *
 * @package Geeky\CV
 */
class Parser
{

    /**
     * @var array
     */
    public $xml = [];

    /**
     * @param $resume_url
     *
     * @return bool|string
     */
    public function parse($resume_url)
    {
        if ($resume_url) {
            $this->xml = new \SimpleXMLElement($this->parseCvToXml($resume_url));
            return $this->extractCVData();
        }

        return FALSE;
    }

    /**
     * @param $resume_url
     *
     * @return mixed
     */
    public function parseCvToXml($resume_url)
    {
        if (is_file($resume_url)) {
            $resume_url = base64_encode(file_get_contents($resume_url));
        }

        $client = new \SoapClient(config('cvparser.service_url'));

        $result = $client->ProcessCV(
            [
                'document_url' => $resume_url,
                'account'      => 'theGeeky'
            ]
        );

        return $result->hrxml;
    }

    /**
     * @return string
     */
    public function extractCVData()
    {
        if ((string)$this->xml == 'document need to be specified!') {
            return 'false';
        }

        if ((string)$this->xml == 'Too Many Requests') {
            throw new TooManyRequestsHttpException;
        }

        $data['contact_info']           = $this->extractContactInfo();
        $data['experiences']            = $this->extractExperienceInfo();
        $data['skills']                 = $this->extractSkillsInfo();
        $data['education']              = $this->extractEducationInfo();
        $data['languages']              = $this->extractLanguagesInfo();
        $data['additional_information'] = $this->extractAdittionalInfo();
        $data['summary']                = (string)$this->xml->StructuredXMLResume->ExecutiveSummary;
        $data['objective']              = (string)$this->xml->StructuredXMLResume->Objective;
        $data['revision_date']          = (string)$this->xml->StructuredXMLResume->RevisionDate;
        $data['text_resume']            = (string)$this->xml->NonXMLResume->TextResume;

        return $data;
    }

    /**
     * @return array
     */
    private function extractContactInfo()
    {
        $contact_info = $this->xml->StructuredXMLResume->ContactInfo;
        $data         = [];

        if ($contact_info) {
            $contact_method = $contact_info->ContactMethod;

            $data['full_name']      = $this->getValue($contact_info, 'PersonName', 'FormattedName');
            $data['first_name']     = $this->getValue($contact_info, 'PersonName', 'GivenName');
            $data['last_name']      = $this->getValue($contact_info, 'PersonName', 'FamilyName');
            $data['sex']            = $this->getValue($contact_info, 'PersonName', 'sex');
            $data['email']          = $this->getValue($contact_method, 'InternetEmailAddress');
            $data['mobile']         = $this->ExtractMobileNumber();
            $data['telephone']      = $this->ExtractTelephoneNumber();
            $data['postal_code']    = $this->getValue($contact_method->PostalAddress, 'CountryCode');
            $data['city']           = $this->getValue($contact_method->PostalAddress, 'Municipality');
            $data['nationality']    = $this->getValue($this->xml->StructuredXMLResume, 'Nationality');
            $data['birth_date']     = $this->getValue($this->xml->StructuredXMLResume, 'Date', 'AnyDate');
            $data['marital_status'] = $this->getValue($this->xml->StructuredXMLResume, 'MaritalStatus');
        }

        return $data;
    }

    /**
     * @return array
     */
    private function extractExperienceInfo()
    {
        $experiences = $this->xml->StructuredXMLResume->EmploymentHistory;
        $data        = [];

        if ($experiences) {
            $experiences = $experiences->EmployerOrg;

            foreach ($experiences as $experience) {
                array_push($data, [
                    'company_name'   => (string)$experience->EmployerOrgName,
                    'job_title'      => $this->getValue($experience->PositionHistory, 'Title'),
                    'city'           => $this->getValue($experience->EmployerContactInfo, 'LocationSummary', 'Municipality'),
                    'country_code'   => $this->getValue($experience->EmployerContactInfo, 'LocationSummary', 'CountryCode'),
                    'start_date'     => $this->getValue($experience->PositionHistory, 'StartDate', 'YearMonth'),
                    'end_date'       => $this->getValue($experience->PositionHistory, 'EndDate', 'YearMonth'),
                    'months_Of_work' => $this->getValue($experience->PositionHistory, 'UserArea', 'DaxPositionHistoryUserArea', 'MonthsOfWork'),
                    'job_category'   => $this->getValue($experience, 'JobCategory', 'CategoryCode'),
                    'domain_name'    => $this->getValue($experience->EmployerContactInfo, 'InternetDomainName'),
                    'Description'    => str_replace("\n", "", $this->getValue($experience->PositionHistory, 'Description'))
                ]);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    private function extractSkillsInfo()
    {
        $skills = $this->xml->StructuredXMLResume->Competency;
        $data   = [];

        if ($skills) {
            foreach ($skills as $skill) {
                array_push($data, [
                    'name'              => $this->getValue($skill->attributes()->name),
                    'description'       => $this->getValue($skill->attributes()->description),
                    'skill_proficiency' => $this->getValue($skill->CompetencyWeight[1], 'StringValue')
                ]);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    function extractAdittionalInfo()
    {

        $info = $this->xml->UserArea->DaxResumeUserArea;
        $data = [];

        if ($info) {
            $info                                                        = $info->AdditionalPersonalData;
            $experience                                                  = $info->ExperienceSummary;
            $data['total_months_of_work_experience']                     = $this->getValue($experience, 'TotalMonthsOfWorkExperience');
            $data['total_years_of_work_experience']                      = $this->getValue($experience, 'TotalYearsOfWorkExperience');
            $data['highest_educational_level']                           = $this->getValue($experience, 'HighestEducationalLevel');
            $data['total_months_of_management_work_experience']          = $this->getValue($experience, 'TotalMonthsOfManagementWorkExperience');
            $data['total_years_of_management_work_experience']           = $this->getValue($experience, 'TotalYearsOfManagementWorkExperience');
            $data['total_monthsOf_low_level_management_work_experience'] = $this->getValue($experience, 'TotalMonthsOfLowLevelManagementWorkExperience');
            $data['total_years_ofLow_level_management_work_experience']  = $this->getValue($experience, 'TotalYearsOfLowLevelManagementWorkExperience');
            $data['executive_brief']                                     = $this->getValue($experience, 'ExecutiveBrief');
            $data['ManagementRecord']                                    = $this->getValue($experience, 'ManagementRecord');
            $data['Hobbies']                                             = $this->getValue($info, 'Hobbies');
        }

        return $data;
    }

    /**
     * @return array
     */
    private function extractLanguagesInfo()
    {
        $languages = $this->xml->StructuredXMLResume->Languages;
        $data      = [];

        if ($languages) {
            foreach ($languages->Language as $language) {
                array_push($data, [
                    'LanguageCode' => $this->getValue($language, 'LanguageCode'),
                    'read'         => $this->getValue($language, 'Read'),
                    'write'        => $this->getValue($language, 'Write'),
                    'speak'        => $this->getValue($language, 'Speak'),
                    'comments'     => $this->getValue($language, 'Comments')
                ]);
            }
        }

        return $data;

    }

    /**
     * @return array
     */
    private function extractEducationInfo()
    {
        $education = $this->xml->StructuredXMLResume->EducationHistory;
        $data      = [];

        if ($education) {
            foreach ($education->SchoolOrInstitution as $education) {
                array_push($data, [
                    'SchoolName'        => $this->getValue($education, 'SchoolName'),
                    'Major'             => $this->getValue($education, 'Major'),
                    'organization_unit' => $this->getValue($education, 'OrganizationUnit'),
                    'city'              => $this->getValue($education, 'LocationSummary', 'Municipality'),
                    'country_code'      => $this->getValue($education, 'LocationSummary', 'CountryCode'),
                    'degree_name'       => $this->getValue($education, 'Degree', 'DegreeName'),
                    'degree_date'       => $this->getValue($education, 'Degree', 'DegreeDate', 'YearMonth'),
                    'start_date'        => $this->getValue($education, 'DatesOfAttendance', 'StartDate', 'Year'),
                    'end_date'          => $this->getValue($education, 'DatesOfAttendance', 'EndDate', 'Year'),
                    'comments'          => $this->getValue($education, 'Comments')
                ]);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    private function ExtractMobileNumber()
    {
        $mobile = $this->xml->StructuredXMLResume->ContactInfo->ContactMethod->Mobile;
        $data   = [];

        if ($mobile) {
            $data['formatted_number']           = $this->getValue($mobile, 'FormattedNumber');
            $data['international_country_code'] = $this->getValue($mobile, 'InternationalCountryCode');
            $data['subscriber_number']          = $this->getValue($mobile, 'SubscriberNumber');
        }

        return $data;
    }


    /**
     * @return array
     */
    private function ExtractTelephoneNumber()
    {
        $telephone = $this->xml->StructuredXMLResume->ContactInfo->ContactMethod->Telephone;
        $data      = [];

        if ($telephone) {
            $data['formatted_number']           = $this->getValue($telephone, 'FormattedNumber');
            $data['international_country_code'] = $this->getValue($telephone, 'InternationalCountryCode');
            $data['subscriber_number']          = $this->getValue($telephone, 'SubscriberNumber');
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        $args_count = func_num_args();
        $object     = func_get_arg(0);

        for ($i = 1; $i < $args_count; $i++) {
            if (isset($object->{func_get_arg($i)})) {
                $object = $object->{func_get_arg($i)};
                continue;
            }

            return '';
        }

        return (string)$object;
    }


}
