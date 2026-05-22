<?php

namespace App\Enums;

enum CourseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
    case DeletedByMentor = 'deleted_by_mentor';
    case HiddenByAdmin = 'hidden_by_admin';
}
